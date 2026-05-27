<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Notification;
use App\Models\User;

class StockMovementController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:IN,OUT',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $product = Product::find($validated['product_id']);

        // If OUT, check if enough stock
        if ($validated['type'] === 'OUT') {
            if ($product->getStockAtWarehouse($validated['warehouse_id']) < $validated['quantity']) {
                return response()->json(['message' => 'Insufficient stock in this warehouse'], 422);
            }
        }

        $movement = DB::transaction(function() use ($validated, $request, $product) {
            $validated['user_id'] = $request->user()->id ?? 1;
            $move = StockMovement::create($validated);

            // Check for low stock alert
            if ($product->getTotalStock() < $product->min_threshold) {
                $admins = User::whereHas('role', function($q) {
                    $q->whereIn('slug', ['admin', 'manager']);
                })->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'Stok Menipis: ' . $product->name,
                        'message' => "Stok produk {$product->name} (SKU: {$product->sku}) telah mencapai batas minimum threshold.",
                        'type' => 'LOW_STOCK'
                    ]);
                }
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $validated['user_id'],
                'action' => 'STOCK_' . $validated['type'],
                'model_type' => 'StockMovement',
                'model_id' => $move->id,
                'new_values' => $move->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $move;
        });

        return response()->json($movement, 201);
    }

    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'user', 'supplier']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return $query->latest()->paginate(20);
    }
}
