<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Product::all();
        $totalProducts = $products->count();
        $totalWarehouses = Warehouse::count();
        $totalStock = StockMovement::where('type', 'IN')->sum('quantity') - StockMovement::where('type', 'OUT')->sum('quantity');
        
        $totalValue = $products->sum(function($product) {
            return $product->getInventoryValue();
        });

        $lowStockProducts = Product::with('stockMovements')->get()->filter(function($product) {
            return $product->getTotalStock() < $product->min_threshold;
        })->values();

        $recentMovements = StockMovement::with(['product', 'warehouse', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        $warehouseStock = Warehouse::all()->map(function($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'latitude' => $warehouse->latitude,
                'longitude' => $warehouse->longitude,
                'city' => $warehouse->city,
                'stock' => StockMovement::where('warehouse_id', $warehouse->id)->where('type', 'IN')->sum('quantity') - 
                           StockMovement::where('warehouse_id', $warehouse->id)->where('type', 'OUT')->sum('quantity')
            ];
        });

        return response()->json([
            'summary' => [
                'total_products' => $totalProducts,
                'total_warehouses' => $totalWarehouses,
                'total_stock' => $totalStock,
                'total_value' => $totalValue,
            ],
            'low_stock_alerts' => $lowStockProducts,
            'recent_movements' => $recentMovements,
            'warehouse_distribution' => $warehouseStock
        ]);
    }
}
