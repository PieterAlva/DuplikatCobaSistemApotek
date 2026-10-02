<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $products = Product::query()->visibleTo($user);
        $users = User::query();

        if (in_array($user->role, ['admin_salam_sehat', 'admin_badan_sehat', 'cashier'], true)) {
            $users->where('pharmacy_id', $user->pharmacy_id);
        } elseif ($user->role === 'supplier') {
            $users->where('supplier_id', $user->supplier_id);
        }

        $expiryBatches = InventoryBatch::query()
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', today()->addDays(30)->toDateString())
            ->whereHas('product', fn ($query) => $query->visibleTo($user));
        $expiryAlertCount = in_array($user->role, [
            'owner',
            'admin_salam_sehat',
            'admin_badan_sehat',
            'warehouse_admin',
        ], true) ? (clone $expiryBatches)->count() : 0;
        $expiryAlerts = in_array($user->role, [
            'owner',
            'admin_salam_sehat',
            'admin_badan_sehat',
            'warehouse_admin',
        ], true)
            ? (clone $expiryBatches)
                ->with(['product:id,name,sku,pharmacy_id', 'product.pharmacy:id,name'])
                ->orderBy('expiry_date')
                ->limit(8)
                ->get()
                ->map(fn (InventoryBatch $batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date->toDateString(),
                    'quantity_remaining' => $batch->quantity_remaining,
                    'days_remaining' => today()->diffInDays($batch->expiry_date, false),
                    'product_name' => $batch->product->name,
                    'sku' => $batch->product->sku,
                    'pharmacy_id' => $batch->product->pharmacy_id,
                    'pharmacy_name' => $batch->product->pharmacy?->name,
                ])
            : [];

        return response()->json([
            'product_count' => (clone $products)->where('is_active', true)->count(),
            'low_stock_count' => (clone $products)->where('is_active', true)
                ->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'inventory_value' => (int) (clone $products)->where('is_active', true)
                ->selectRaw('COALESCE(SUM(current_stock * purchase_price), 0) as total')->value('total'),
            'user_count' => $users->where('is_active', true)->count(),
            'pharmacy' => $user->pharmacy?->name ?? match ($user->role) {
                'owner' => 'Semua apotek',
                'warehouse_admin' => 'Gudang · semua apotek',
                'supplier' => $user->supplier?->name ?? 'Supplier',
                default => null,
            },
            'pharmacies' => in_array($user->role, ['owner', 'warehouse_admin'], true)
                ? Pharmacy::query()->where('is_active', true)->get(['id', 'name'])
                : [],
            'expiry_alert_count' => $expiryAlertCount,
            'expiry_alerts' => $expiryAlerts,
        ]);
    }
}
