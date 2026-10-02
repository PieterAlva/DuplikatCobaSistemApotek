<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCashier($user);
        $sales = Sale::query()->with(['cashier:id,name', 'pharmacy:id,name', 'items'])
            ->latest();

        if ($this->isBranchScoped($user)) {
            $pharmacyId = $this->branchPharmacyId($user);
            $sales->where('pharmacy_id', $pharmacyId);
        }
        if ($request->boolean('today')) {
            $sales->whereDate('created_at', today());
        }

        return response()->json(['data' => $sales->paginate(30)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCashier($user);
        $data = $request->validate([
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['sometimes', 'required', 'string', 'in:cash,card,transfer,qris'],
            'reference' => ['nullable', 'string', 'max:150'],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $pharmacyId = $this->resolvePharmacyId($user, $data['pharmacy_id'] ?? null);
        $paymentCode = $data['payment_method'] ?? 'cash';
        $sale = DB::transaction(function () use ($data, $pharmacyId, $user, $paymentCode, $request) {
            $paymentMethod = PaymentMethod::query()
                ->where('code', $paymentCode)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if (! $paymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method' => ['Metode pembayaran tidak tersedia atau tidak aktif.'],
                ]);
            }

            $customerId = $data['customer_id'] ?? null;
            if ($customerId !== null) {
                $customerExists = Customer::query()
                    ->whereKey($customerId)
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('is_active', true)
                    ->exists();
                if (! $customerExists) {
                    throw ValidationException::withMessages([
                        'customer_id' => ['Pelanggan tidak aktif atau tidak terdaftar pada apotek ini.'],
                    ]);
                }
            }

            $requestedItems = collect($data['items'])->keyBy('product_id');
            $products = Product::query()
                ->where('pharmacy_id', $pharmacyId)
                ->where('is_active', true)
                ->whereIn('id', $requestedItems->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $requestedItems->count()) {
                throw ValidationException::withMessages(['items' => ['Ada produk yang tidak tersedia pada apotek ini.']]);
            }

            $subtotal = 0;
            $allocations = [];
            foreach ($requestedItems as $productId => $item) {
                $product = $products->get($productId);
                if ($product->current_stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$product->name} tidak mencukupi."],
                    ]);
                }
                $batches = InventoryBatch::query()
                    ->where('product_id', $product->id)
                    ->where('quantity_remaining', '>', 0)
                    ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('expiry_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $trackedTotal = (int) $batches->sum('quantity_remaining');
                $legacyRemaining = max(0, $product->current_stock - $trackedTotal);
                $availableTracked = min($product->current_stock, (int) $batches
                    ->filter(fn (InventoryBatch $batch) => $batch->expiry_date === null || $batch->expiry_date->toDateString() >= today()->toDateString())
                    ->sum('quantity_remaining'));
                $available = $availableTracked + $legacyRemaining;
                if ($available < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok kedaluwarsa atau stok batch tersedia {$product->name} tidak mencukupi."],
                    ]);
                }

                $remaining = (int) $item['quantity'];
                $trackedBudget = $availableTracked;
                $productAllocations = [];
                foreach ($batches as $batch) {
                    if ($remaining === 0) {
                        break;
                    }
                    if ($batch->expiry_date !== null && $batch->expiry_date->toDateString() < today()->toDateString()) {
                        continue;
                    }
                    $take = min($remaining, $trackedBudget, $batch->quantity_remaining);
                    if ($take > 0) {
                        $productAllocations[] = ['batch' => $batch, 'quantity' => $take];
                        $remaining -= $take;
                        $trackedBudget -= $take;
                    }
                }
                if ($remaining > 0) {
                    $productAllocations[] = ['batch' => null, 'quantity' => $remaining];
                }
                $allocations[$productId] = $productAllocations;
                $subtotal += $product->selling_price * $item['quantity'];
            }

            if ($data['amount_paid'] < $subtotal || ($paymentCode !== 'cash' && $data['amount_paid'] !== $subtotal)) {
                throw ValidationException::withMessages([
                    'amount_paid' => [$paymentCode === 'cash'
                        ? 'Pembayaran kurang dari total transaksi.'
                        : 'Pembayaran non-tunai harus sama dengan total transaksi.'],
                ]);
            }

            $sale = Sale::create([
                'pharmacy_id' => $pharmacyId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'receipt_number' => 'SLS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'amount_paid' => $data['amount_paid'],
                'change_due' => $data['amount_paid'] - $subtotal,
                'payment_method' => $paymentCode,
            ]);
            PaymentRecord::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $paymentMethod->id,
                'method_name' => $paymentMethod->name,
                'amount' => $subtotal,
                'reference' => $data['reference'] ?? null,
                'paid_at' => now(),
            ]);

            foreach ($requestedItems as $productId => $item) {
                $product = $products->get($productId);
                $before = $product->current_stock;
                $after = $before - $item['quantity'];
                $product->update(['current_stock' => $after]);
                foreach ($allocations[$productId] as $allocation) {
                    /** @var InventoryBatch|null $batch */
                    $batch = $allocation['batch'];
                    $quantity = $allocation['quantity'];
                    if ($batch !== null) {
                        $batch->decrement('quantity_remaining', $quantity);
                    }
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'inventory_batch_id' => $batch?->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $product->selling_price,
                        'line_total' => $product->selling_price * $quantity,
                    ]);
                    $product->stockMovements()->create([
                        'user_id' => $user->id,
                        'inventory_batch_id' => $batch?->id,
                        'type' => 'sale',
                        'quantity' => $quantity,
                        'stock_before' => $before,
                        'stock_after' => $before - $quantity,
                        'reason' => "Penjualan {$sale->receipt_number}",
                    ]);
                    $before -= $quantity;
                }
            }

            AuditLogger::record(
                $request,
                'sale.created',
                $pharmacyId,
                $sale,
                $sale->id,
                [],
                ['id' => $sale->id, 'item_count' => $requestedItems->count(), 'total' => $subtotal],
            );

            return $sale->load(['cashier:id,name', 'pharmacy:id,name', 'customer', 'items', 'paymentRecords.paymentMethod']);
        }, 3);

        return response()->json(['data' => $sale], 201);
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeCashier($request->user());
        $this->authorizeSaleAccess($request->user(), $sale);

        return response()->json(['data' => $sale->load(['cashier:id,name', 'pharmacy:id,name', 'items'])]);
    }

    private function authorizeCashier(User $user): void
    {
        abort_unless(
            in_array($user->role, ['owner', 'admin_salam_sehat', 'admin_badan_sehat', 'cashier'], true),
            403,
            'Role Anda tidak memiliki akses ke transaksi kasir.',
        );
    }

    private function authorizeSaleAccess(User $user, Sale $sale): void
    {
        if ($this->isBranchScoped($user)) {
            abort_unless($sale->pharmacy_id === $this->branchPharmacyId($user), 404);
        }
    }

    private function resolvePharmacyId(User $user, ?int $requestedId): int
    {
        if ($user->role === 'cashier') {
            $pharmacyId = $this->branchPharmacyId($user);
            abort_if($requestedId !== null && $requestedId !== $pharmacyId, 404);

            return $pharmacyId;
        }

        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacyId = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($pharmacyId, 404);

            return $pharmacyId;
        }

        abort_unless($requestedId !== null, 422, 'Apotek wajib dipilih.');

        return $requestedId;
    }

    private function isBranchScoped(User $user): bool
    {
        return $user->role === 'cashier' || isset(User::PHARMACY_ROLES[$user->role]);
    }

    private function branchPharmacyId(User $user): int
    {
        if ($user->role === 'cashier') {
            abort_unless($user->pharmacy_id, 404, 'Akun kasir belum terhubung ke apotek.');

            return $user->pharmacy_id;
        }

        $pharmacyId = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
        abort_unless($pharmacyId, 404);

        return $pharmacyId;
    }
}
