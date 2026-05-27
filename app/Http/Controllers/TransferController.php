<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\TransferItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\AuditLog;
use App\Jobs\ProcessStockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $transfer = DB::transaction(function() use ($validated, $request) {
            $transfer = StockTransfer::create([
                'transfer_number' => 'TRF-' . strtoupper(Str::random(8)),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'user_id' => $request->user()->id ?? 1,
                'status' => 'PENDING',
                'notes' => $validated['notes'] ?? null
            ]);

            foreach ($validated['items'] as $item) {
                // Check stock at origin
                $product = Product::find($item['product_id']);
                if ($product->getStockAtWarehouse($validated['from_warehouse_id']) < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                TransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $transfer->user_id,
                'action' => 'TRANSFER_REQUEST',
                'model_type' => 'StockTransfer',
                'model_id' => $transfer->id,
                'new_values' => $transfer->load('items')->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $transfer;
        });

        return response()->json($transfer->load('items.product'), 201);
    }

    public function approve(StockTransfer $transfer, Request $request)
    {
        if ($transfer->status !== 'PENDING') {
            return response()->json(['message' => 'Transfer is not in pending status'], 422);
        }

        $approverId = $request->user()->id ?? 1;
        
        // Dispatch job for parallel processing
        ProcessStockTransfer::dispatch($transfer, $approverId);

        $transfer->update(['status' => 'APPROVED']); // Set to approved while job processes

        // Audit Log
        AuditLog::create([
            'user_id' => $approverId,
            'action' => 'TRANSFER_APPROVE_DISPATCHED',
            'model_type' => 'StockTransfer',
            'model_id' => $transfer->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['message' => 'Transfer approval is being processed in background.', 'transfer' => $transfer]);
    }

    public function index()
    {
        return StockTransfer::with(['fromWarehouse', 'toWarehouse', 'user', 'approver'])
            ->latest()
            ->paginate(15);
    }
}
