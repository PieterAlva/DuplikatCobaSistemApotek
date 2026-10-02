<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'low_stock' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
        ]);
        $products = Product::query()->visibleTo($request->user())
            ->with(['pharmacy:id,name', 'supplier:id,name'])->where('is_active', true);
        $user = $request->user();
        if ($user->role === 'cashier') {
            abort_unless($user->pharmacy_id, 404, 'Akun kasir belum terhubung ke apotek.');
            abort_if(isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== $user->pharmacy_id, 404);
            $products->where('pharmacy_id', $user->pharmacy_id);
        }
        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacyId = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($pharmacyId, 404);
            abort_if(isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== $pharmacyId, 404);
            $products->where('pharmacy_id', $pharmacyId);
        } elseif (! empty($filters['pharmacy_id']) && in_array($user->role, ['owner', 'warehouse_admin'], true)) {
            $products->where('pharmacy_id', $filters['pharmacy_id']);
        } elseif (isset($filters['pharmacy_id']) && $user->role === 'supplier') {
            abort(404);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $products->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%"));
        }
        if ($filters['low_stock'] ?? false) {
            $products->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        return response()->json([
            'data' => $products->latest()->paginate($filters['per_page'] ?? 20),
            'pharmacies' => in_array($request->user()->role, ['owner', 'warehouse_admin'], true)
                ? Pharmacy::query()->where('is_active', true)->get(['id', 'name'])
                : [],
            'suppliers' => $request->user()->role === 'owner'
                ? Supplier::query()->where('is_active', true)->get(['id', 'name'])
                : [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeInventoryWrite($request->user());
        $data = $this->validatedProduct($request);
        $data['pharmacy_id'] = $this->resolvePharmacyId($request->user(), $data['pharmacy_id'] ?? null);
        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            if ($product->current_stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'inbound',
                    'quantity' => $product->current_stock,
                    'stock_before' => 0,
                    'stock_after' => $product->current_stock,
                    'reason' => 'Stok awal',
                ]);
            }

            AuditLogger::record(
                $request,
                'product.created',
                $product->pharmacy_id,
                $product,
                $product->id,
                [],
                ['id' => $product->id, 'sku' => $product->sku, 'quantity' => $product->current_stock],
            );

            return $product;
        });

        return response()->json(['data' => $product->load(['pharmacy:id,name', 'supplier:id,name'])], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProductAccess($request->user(), $product);

        return response()->json(['data' => $product->load(['pharmacy:id,name', 'supplier:id,name'])]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeInventoryWrite($request->user());
        $this->authorizeProductAccess($request->user(), $product);
        $data = $this->validatedProduct($request, $product);
        unset($data['pharmacy_id'], $data['current_stock']);
        $oldSku = $product->sku;
        $product->update($data);
        AuditLogger::record(
            $request,
            'product.updated',
            $product->pharmacy_id,
            $product,
            $product->id,
            ['sku' => $oldSku],
            ['id' => $product->id, 'sku' => $product->sku],
        );

        return response()->json(['data' => $product->fresh()->load(['pharmacy:id,name', 'supplier:id,name'])]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeInventoryWrite($request->user());
        $this->authorizeProductAccess($request->user(), $product);
        $product->update(['is_active' => false]);
        AuditLogger::record(
            $request,
            'product.deactivated',
            $product->pharmacy_id,
            $product,
            $product->id,
            ['is_active' => true],
            ['id' => $product->id, 'sku' => $product->sku, 'is_active' => false],
        );

        return response()->json(['message' => 'Produk dinonaktifkan.']);
    }

    public function adjustStock(Request $request, int $product): JsonResponse
    {
        $user = $request->user();
        $this->authorizeInventoryWrite($user);
        $data = $request->validate([
            'type' => ['required', Rule::in(['inbound', 'outbound'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $updated = DB::transaction(function () use ($data, $product, $user, $request) {
            $item = Product::query()->visibleTo($user)->lockForUpdate()->findOrFail($product);
            $this->authorizeProductAccess($user, $item);
            $before = $item->current_stock;
            $after = $data['type'] === 'inbound' ? $before + $data['quantity'] : $before - $data['quantity'];
            abort_if($after < 0, 422, 'Stok tidak mencukupi untuk pengeluaran tersebut.');

            $item->update(['current_stock' => $after]);
            $item->stockMovements()->create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $data['reason'],
            ]);
            AuditLogger::record(
                $request,
                'product.stock_adjusted',
                $item->pharmacy_id,
                $item,
                $item->id,
                ['stock_before' => $before],
                [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'quantity' => $data['quantity'],
                    'stock_before' => $before,
                    'stock_after' => $after,
                ],
            );

            return $item->fresh()->load(['pharmacy:id,name', 'supplier:id,name']);
        });

        return response()->json(['data' => $updated]);
    }

    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $pharmacyId = $product?->pharmacy_id ?? $request->input('pharmacy_id', $request->user()->pharmacy_id);

        return $request->validate([
            'pharmacy_id' => [$product ? 'sometimes' : 'required', 'integer', 'exists:pharmacies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->where('pharmacy_id', $pharmacyId)->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->where('pharmacy_id', $pharmacyId)->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:40'],
            'purchase_price' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'current_stock' => [$product ? 'prohibited' : 'sometimes', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function resolvePharmacyId(User $user, ?int $requestedId): int
    {
        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacy = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->firstOrFail();

            return $pharmacy->id;
        }

        abort_unless($requestedId !== null, 422, 'Apotek wajib dipilih.');

        return $requestedId;
    }

    private function authorizeInventoryWrite(User $user): void
    {
        abort_unless(
            in_array($user->role, ['owner', 'admin_salam_sehat', 'admin_badan_sehat', 'warehouse_admin'], true),
            403,
            'Role Anda hanya dapat melihat inventaris.',
        );
    }

    private function authorizeProductAccess(User $user, Product $product): void
    {
        if ($user->role === 'cashier') {
            abort_unless($user->pharmacy_id, 404);
            abort_unless($product->pharmacy_id === $user->pharmacy_id, 404);
        } elseif (isset(User::PHARMACY_ROLES[$user->role])) {
            $expected = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($product->pharmacy_id === $expected, 404);
        } elseif ($user->role === 'supplier') {
            abort_unless($product->supplier_id === $user->supplier_id, 404);
        }
    }
}
