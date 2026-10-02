<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizePurchaseAccess($user);
        $filters = $request->validate([
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'status' => ['nullable', 'string', 'in:ordered,partially_received,received,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        if ($this->branchScoped($user)) {
            abort_if(
                isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== $this->branchId($user),
                404,
            );
        }

        $purchases = Purchase::query()
            ->with(['pharmacy:id,name', 'supplier:id,name', 'user:id,name', 'items.product:id,name,sku'])
            ->when($this->branchScoped($user), fn ($query) => $query->where('pharmacy_id', $this->branchId($user)))
            ->when(! $this->branchScoped($user) && ! empty($filters['pharmacy_id']), fn ($query) => $query->where('pharmacy_id', $filters['pharmacy_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest();

        return response()->json(['data' => $purchases->paginate($filters['per_page'] ?? 30)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizePurchaseAccess($user);
        $data = $request->validate([
            'pharmacy_id' => [$this->branchScoped($user) ? 'nullable' : 'required', 'integer', 'exists:pharmacies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $pharmacyId = $this->branchScoped($user)
            ? $this->branchId($user)
            : (int) $data['pharmacy_id'];
        if (isset($data['pharmacy_id'])) {
            abort_if((int) $data['pharmacy_id'] !== $pharmacyId, 404);
        }

        $productIds = collect($data['items'])->pluck('product_id')->unique();
        $products = Product::query()->where('pharmacy_id', $pharmacyId)
            ->whereIn('id', $productIds)->get()->keyBy('id');
        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => ['Semua produk harus berasal dari apotek purchase order.']]);
        }

        $subtotal = collect($data['items'])->sum(
            fn (array $item) => (int) $item['quantity_ordered'] * (int) $item['unit_cost'],
        );
        $purchase = DB::transaction(function () use ($data, $pharmacyId, $user, $subtotal, $request) {
            $purchase = Purchase::create([
                'pharmacy_id' => $pharmacyId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $user->id,
                'purchase_number' => 'PO-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
                'status' => 'ordered',
                'ordered_at' => $data['ordered_at'] ?? today(),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = (int) $item['quantity_ordered'] * (int) $item['unit_cost'];
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }
            AuditLogger::record(
                $request,
                'purchase.created',
                $pharmacyId,
                $purchase,
                $purchase->id,
                [],
                [
                    'id' => $purchase->id,
                    'status' => $purchase->status,
                    'item_count' => count($data['items']),
                    'total' => $subtotal,
                ],
            );

            return $purchase->load(['pharmacy:id,name', 'supplier:id,name', 'user:id,name', 'items.product:id,name,sku']);
        }, 3);

        return response()->json(['data' => $purchase], 201);
    }

    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorizePurchaseAccess($request->user());
        $this->authorizePurchaseBranch($request->user(), $purchase);

        return response()->json([
            'data' => $purchase->load(['pharmacy:id,name', 'supplier:id,name', 'user:id,name', 'items.product:id,name,sku']),
        ]);
    }

    public function receive(Request $request, Purchase $purchase): JsonResponse
    {
        $user = $request->user();
        $this->authorizePurchaseAccess($user);
        $this->authorizePurchaseBranch($user, $purchase);
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.purchase_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0', 'max:1000000'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $purchase = DB::transaction(function () use ($purchase, $data, $user, $request) {
            $lockedPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $this->authorizePurchaseBranch($user, $lockedPurchase);
            if ($lockedPurchase->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => ['Purchase yang dibatalkan tidak dapat diterima.']]);
            }
            if ($lockedPurchase->status === 'received') {
                throw ValidationException::withMessages(['status' => ['Purchase ini sudah diterima seluruhnya.']]);
            }

            $oldStatus = $lockedPurchase->status;
            $receivedDelta = 0;
            $items = PurchaseItem::query()->where('purchase_id', $lockedPurchase->id)
                ->whereIn('id', collect($data['items'])->pluck('purchase_item_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($items->count() !== count($data['items'])) {
                throw ValidationException::withMessages(['items' => ['Item harus berasal dari purchase order ini.']]);
            }

            $productIds = $items->pluck('product_id')->unique();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')
                ->lockForUpdate()->get()->keyBy('id');
            foreach ($data['items'] as $receipt) {
                $item = $items->get($receipt['purchase_item_id']);
                $targetReceived = (int) $receipt['quantity_received'];
                if ($targetReceived < $item->quantity_received) {
                    throw ValidationException::withMessages([
                        'items' => ['Jumlah diterima harus kumulatif dan tidak boleh berkurang.'],
                    ]);
                }
                if ($targetReceived > $item->quantity_ordered) {
                    throw ValidationException::withMessages([
                        'items' => ['Jumlah diterima tidak boleh melebihi jumlah pesanan.'],
                    ]);
                }

                $delta = $targetReceived - $item->quantity_received;
                if ($delta === 0) {
                    continue;
                }
                $receivedDelta += $delta;

                $product = $products->get($item->product_id);
                if (! $product || $product->pharmacy_id !== $lockedPurchase->pharmacy_id) {
                    throw ValidationException::withMessages([
                        'items' => ['Produk item tidak berasal dari apotek purchase order.'],
                    ]);
                }
                $batchNumber = $receipt['batch_number'] ?? $item->batch_number;
                if ($item->batch_number && $batchNumber !== $item->batch_number) {
                    throw ValidationException::withMessages([
                        'items' => ['Batch number harus sama untuk penerimaan parsial pada item ini.'],
                    ]);
                }
                $batchNumber ??= $lockedPurchase->purchase_number.'-'.$item->id;
                $expiryDate = $receipt['expiry_date'] ?? $item->expiry_date?->toDateString();
                if ($item->expiry_date && $expiryDate !== $item->expiry_date->toDateString()) {
                    throw ValidationException::withMessages([
                        'items' => ['Tanggal kedaluwarsa harus sama untuk penerimaan parsial pada item ini.'],
                    ]);
                }

                $batch = InventoryBatch::query()->firstOrCreate(
                    ['product_id' => $product->id, 'batch_number' => $batchNumber],
                    [
                        'supplier_id' => $lockedPurchase->supplier_id,
                        'expiry_date' => $expiryDate,
                        'quantity_received' => 0,
                        'quantity_remaining' => 0,
                        'purchase_price' => $item->unit_cost,
                    ],
                );
                if ($batch->purchase_price !== $item->unit_cost || $batch->expiry_date?->toDateString() !== $expiryDate) {
                    throw ValidationException::withMessages([
                        'items' => ['Batch number sudah digunakan dengan data berbeda.'],
                    ]);
                }

                $before = $product->current_stock;
                $after = $before + $delta;
                $product->update(['current_stock' => $after]);
                $batch->increment('quantity_received', $delta);
                $batch->increment('quantity_remaining', $delta);
                $item->update([
                    'quantity_received' => $targetReceived,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ]);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'inventory_batch_id' => $batch->id,
                    'type' => 'inbound',
                    'quantity' => $delta,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => "Penerimaan {$lockedPurchase->purchase_number}",
                ]);
            }

            $lockedPurchase->load('items');
            $allReceived = $lockedPurchase->items->every(
                fn (PurchaseItem $item) => $item->quantity_received === $item->quantity_ordered,
            );
            $anyReceived = $lockedPurchase->items->contains(
                fn (PurchaseItem $item) => $item->quantity_received > 0,
            );
            $lockedPurchase->update([
                'status' => $allReceived ? 'received' : ($anyReceived ? 'partially_received' : 'ordered'),
                'received_at' => $allReceived ? now() : null,
            ]);
            if ($receivedDelta > 0) {
                AuditLogger::record(
                    $request,
                    'purchase.received',
                    $lockedPurchase->pharmacy_id,
                    $lockedPurchase,
                    $lockedPurchase->id,
                    ['status' => $oldStatus],
                    [
                        'id' => $lockedPurchase->id,
                        'status' => $lockedPurchase->status,
                        'quantity_delta' => $receivedDelta,
                    ],
                );
            }

            return $lockedPurchase->fresh()->load([
                'pharmacy:id,name', 'supplier:id,name', 'user:id,name', 'items.product:id,name,sku',
            ]);
        }, 3);

        return response()->json(['data' => $purchase]);
    }

    public function cancel(Request $request, Purchase $purchase): JsonResponse
    {
        $user = $request->user();
        $this->authorizePurchaseAccess($user);
        $this->authorizePurchaseBranch($user, $purchase);

        $purchase = DB::transaction(function () use ($purchase, $request) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            abort_unless($purchase->status === 'ordered', 422, 'Hanya purchase yang belum diterima yang dapat dibatalkan.');
            $oldStatus = $purchase->status;
            $purchase->update(['status' => 'cancelled']);
            AuditLogger::record(
                $request,
                'purchase.cancelled',
                $purchase->pharmacy_id,
                $purchase,
                $purchase->id,
                ['status' => $oldStatus],
                ['id' => $purchase->id, 'status' => $purchase->status],
            );

            return $purchase;
        });

        return response()->json(['data' => $purchase->fresh()]);
    }

    private function authorizePurchaseAccess(User $user): void
    {
        abort_unless(
            in_array($user->role, ['owner', 'admin_salam_sehat', 'admin_badan_sehat'], true),
            403,
            'Role Anda tidak memiliki akses ke purchase order.',
        );
        if ($this->branchScoped($user)) {
            abort_unless($this->branchId($user), 403, 'Akun admin belum terhubung ke apotek.');
        }
    }

    private function branchScoped(User $user): bool
    {
        return isset(User::PHARMACY_ROLES[$user->role]);
    }

    private function branchId(User $user): int
    {
        if ($user->pharmacy_id) {
            return $user->pharmacy_id;
        }

        $id = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
        abort_unless($id, 404);

        return $id;
    }

    private function authorizePurchaseBranch(User $user, Purchase $purchase): void
    {
        if ($this->branchScoped($user)) {
            abort_unless($purchase->pharmacy_id === $this->branchId($user), 404);
        }
    }
}
