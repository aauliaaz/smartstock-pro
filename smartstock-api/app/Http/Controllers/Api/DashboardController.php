<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $whFilter = $request->input('warehouse_id');

        $stockQuery = ProductWarehouseStock::query();
        if ($whFilter) $stockQuery->where('warehouse_id', $whFilter);

        $totalStock = (int) $stockQuery->sum('quantity');
        $totalProducts = Product::where('is_active', true)->count();

        $totalValue = DB::table('product_warehouse_stocks')
            ->join('products', 'products.id', '=', 'product_warehouse_stocks.product_id')
            ->when($whFilter, fn ($q) => $q->where('product_warehouse_stocks.warehouse_id', $whFilter))
            ->selectRaw('SUM(product_warehouse_stocks.quantity * products.price_buy) AS total')
            ->value('total') ?? 0;

        $lowStockCount = DB::table('product_warehouse_stocks as pws')
            ->join('products as p', 'p.id', '=', 'pws.product_id')
            ->when($whFilter, fn ($q) => $q->where('pws.warehouse_id', $whFilter))
            ->whereRaw('pws.quantity <= p.min_stock')
            ->where('p.is_active', true)
            ->count();

        $pendingTransfers = StockTransfer::where('status', 'PENDING')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'total_stock' => $totalStock,
                'total_value' => (float) $totalValue,
                'low_stock_count' => $lowStockCount,
                'pending_transfers' => $pendingTransfers,
                'total_warehouses' => Warehouse::where('is_active', true)->count(),
            ],
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $days = min((int) $request->input('days', 30), 90);
        $startDate = now()->subDays($days)->startOfDay();

        $movements = StockMovement::selectRaw('DATE(movement_date) as date, type, SUM(quantity) as total')
            ->where('movement_date', '>=', $startDate)
            ->when($request->input('warehouse_id'), fn ($q, $v) => $q->where('warehouse_id', $v))
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $byDate = [];
        for ($i = $days; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $byDate[$d] = ['date' => $d, 'IN' => 0, 'OUT' => 0, 'TRANSFER_IN' => 0, 'TRANSFER_OUT' => 0];
        }
        foreach ($movements as $m) {
            if (isset($byDate[$m->date])) {
                $byDate[$m->date][$m->type] = (int) $m->total;
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($byDate),
        ]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 50);
        $days = (int) $request->input('days', 30);

        $top = DB::table('stock_movements as sm')
            ->join('products as p', 'p.id', '=', 'sm.product_id')
            ->where('sm.movement_date', '>=', now()->subDays($days))
            ->whereIn('sm.type', ['OUT', 'TRANSFER_OUT'])
            ->selectRaw('p.id, p.sku, p.name, SUM(sm.quantity) as total_out')
            ->groupBy('p.id', 'p.sku', 'p.name')
            ->orderByDesc('total_out')
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'data' => $top]);
    }

    public function warehousesMap(): JsonResponse
    {
        $warehouses = Warehouse::where('is_active', true)
            ->with([])
            ->get(['id', 'code', 'name', 'city', 'latitude', 'longitude'])
            ->map(function (Warehouse $w) {
                return [
                    'id' => $w->id,
                    'code' => $w->code,
                    'name' => $w->name,
                    'city' => $w->city,
                    'latitude' => (float) $w->latitude,
                    'longitude' => (float) $w->longitude,
                    'total_stock' => (int) $w->stocks()->sum('quantity'),
                    'product_count' => $w->stocks()->where('quantity', '>', 0)->count(),
                ];
            });

        return response()->json(['success' => true, 'data' => $warehouses]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $rows = DB::table('product_warehouse_stocks as pws')
            ->join('products as p', 'p.id', '=', 'pws.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pws.warehouse_id')
            ->whereRaw('pws.quantity <= p.min_stock')
            ->where('p.is_active', true)
            ->select(
                'p.id as product_id', 'p.sku', 'p.name as product_name', 'p.min_stock',
                'w.id as warehouse_id', 'w.code as warehouse_code', 'w.name as warehouse_name',
                'pws.quantity'
            )
            ->orderBy('pws.quantity')
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function distribution(Request $request): JsonResponse
    {
        $perWarehouse = DB::table('product_warehouse_stocks as pws')
            ->join('warehouses as w', 'w.id', '=', 'pws.warehouse_id')
            ->where('w.is_active', true)
            ->groupBy('w.id', 'w.code', 'w.name')
            ->select('w.code', 'w.name', DB::raw('SUM(pws.quantity) as total'))
            ->orderByDesc('total')
            ->get();

        return response()->json(['success' => true, 'data' => $perWarehouse]);
    }
}
