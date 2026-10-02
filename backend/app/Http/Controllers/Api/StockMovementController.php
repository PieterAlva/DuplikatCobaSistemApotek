<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['owner', 'warehouse_admin'], true),
            403,
            'Role Anda tidak memiliki akses ke mutasi stok.',
        );
        $filters = $request->validate([
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pharmacyId = null;
        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacyId = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($pharmacyId, 404);
        } elseif (! empty($filters['pharmacy_id'])) {
            $pharmacyId = (int) $filters['pharmacy_id'];
        }

        $movements = StockMovement::query()
            ->with(['product:id,pharmacy_id,name,sku,unit', 'product.pharmacy:id,name', 'user:id,name'])
            ->when($pharmacyId !== null, fn ($query) => $query->whereHas(
                'product',
                fn ($products) => $products->where('pharmacy_id', $pharmacyId),
            ))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($matches) use ($search) {
                    $matches->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($products) => $products
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 10);

        return response()->json([
            'data' => $movements,
            'pharmacies' => in_array($user->role, ['owner', 'warehouse_admin'], true)
                ? Pharmacy::query()->where('is_active', true)->get(['id', 'name'])
                : [],
        ]);
    }
}
