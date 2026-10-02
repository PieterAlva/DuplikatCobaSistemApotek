<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['owner', 'admin_salam_sehat', 'admin_badan_sehat', 'cashier'], true),
            403,
            'Role Anda tidak memiliki akses ke laporan penjualan.',
        );

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
        ]);
        $end = CarbonImmutable::parse($filters['to'] ?? today()->toDateString())->endOfDay();
        $start = CarbonImmutable::parse($filters['from'] ?? today()->startOfMonth()->toDateString())->startOfDay();
        abort_if($start->diffInDays($end) > 92, 422, 'Rentang laporan maksimal 93 hari.');

        $pharmacyId = null;
        $sales = Sale::query()->whereBetween('created_at', [$start, $end]);
        if ($user->role === 'cashier') {
            $pharmacyId = $user->pharmacy_id;
            abort_unless($pharmacyId, 404, 'Akun kasir belum terhubung ke apotek.');
            abort_if(isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== $pharmacyId, 404);
            $sales->where('pharmacy_id', $pharmacyId);
        } elseif (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacyId = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($pharmacyId, 404);
            $sales->where('pharmacy_id', $pharmacyId);
        } elseif (! empty($filters['pharmacy_id'])) {
            $pharmacyId = (int) $filters['pharmacy_id'];
            $sales->where('pharmacy_id', $pharmacyId);
        }

        $products = Product::query()->visibleTo($user)->where('is_active', true);
        if ($pharmacyId !== null) {
            $products->where('pharmacy_id', $pharmacyId);
        }

        $itemsQuery = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->when($pharmacyId !== null, fn ($query) => $query->where('sales.pharmacy_id', $pharmacyId));
        $dailySales = (clone $sales)->selectRaw('DATE(created_at) as date, COUNT(*) as transactions, SUM(total) as total')
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();
        $topProducts = (clone $itemsQuery)
            ->selectRaw('sale_items.product_name, sale_items.sku, SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_name', 'sale_items.sku')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        return response()->json([
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'pharmacies' => $user->role === 'owner' ? Pharmacy::query()->where('is_active', true)->get(['id', 'name']) : [],
            'summary' => [
                'transaction_count' => (clone $sales)->count(),
                'sales_total' => (int) (clone $sales)->sum('total'),
                'items_sold' => (int) (clone $itemsQuery)->sum('sale_items.quantity'),
                'low_stock_count' => (clone $products)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
                'inventory_value' => (int) (clone $products)
                    ->selectRaw('COALESCE(SUM(current_stock * purchase_price), 0) as total')->value('total'),
            ],
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
        ]);
    }
}
