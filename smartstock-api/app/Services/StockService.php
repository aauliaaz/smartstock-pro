<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Algoritma update stok atomic dengan row locking.
     * Mengikuti pseudocode di Tahap 9.2.
     */
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type'];
            $productId = (int) $data['product_id'];
            $warehouseId = (int) $data['warehouse_id'];
            $qty = (int) $data['quantity'];

            if ($qty <= 0) {
                throw new \DomainException('Quantity harus lebih dari 0');
            }

            // Lock row (SQLite ignores FOR UPDATE but MySQL/PG honors it).
            $stock = ProductWarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = ProductWarehouseStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $oldQty = $stock->quantity;

            if (in_array($type, [StockMovement::TYPE_IN, StockMovement::TYPE_TRANSFER_IN], true)) {
                $newQty = $oldQty + $qty;
            } elseif (in_array($type, [StockMovement::TYPE_OUT, StockMovement::TYPE_TRANSFER_OUT], true)) {
                $available = $oldQty - $stock->reserved_quantity;
                if ($available < $qty) {
                    throw new \DomainException("Stok tidak cukup. Tersedia: {$available}, diminta: {$qty}");
                }
                $newQty = $oldQty - $qty;
            } elseif ($type === StockMovement::TYPE_ADJUSTMENT) {
                $newQty = $oldQty + $qty;
                if ($newQty < 0) {
                    throw new \DomainException('Adjustment akan membuat stok negatif');
                }
            } else {
                throw new \DomainException("Tipe movement tidak valid: {$type}");
            }

            $stock->update(['quantity' => $newQty]);

            $movement = StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'type' => $type,
                'quantity' => $qty,
                'unit_price' => $data['unit_price'] ?? 0,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'note' => $data['note'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
            ]);

            AuditLog::record('STOCK_'.$type, $movement, ['quantity_before' => $oldQty], ['quantity_after' => $newQty]);

            return $movement->fresh();
        });
    }

    /**
     * Setelah movement direkam, cek alert stok.
     * Dipanggil setelah transaction commit agar tidak menahan lock.
     */
    public function checkAndAlert(int $productId, int $warehouseId): void
    {
        $product = Product::find($productId);
        if (! $product) return;

        $stock = ProductWarehouseStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
        if (! $stock) return;

        if ($stock->quantity > $product->min_stock) {
            // recovered
            $this->notifications->resolveStockAlerts($productId, $warehouseId);
            return;
        }

        $this->notifications->createLowStockAlert($product, $warehouseId, $stock->quantity);
    }

    /**
     * Algoritma valuasi FIFO (Tahap 9.3).
     */
    public function calculateFifoValuation(int $productId, int $warehouseId, ?string $upToDate = null): array
    {
        $cutoff = $upToDate ?? now()->toDateString();

        $layersIn = StockMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', [StockMovement::TYPE_IN, StockMovement::TYPE_TRANSFER_IN])
            ->whereDate('movement_date', '<=', $cutoff)
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'quantity', 'unit_price', 'movement_date']);

        $totalOut = StockMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', [StockMovement::TYPE_OUT, StockMovement::TYPE_TRANSFER_OUT])
            ->whereDate('movement_date', '<=', $cutoff)
            ->sum('quantity');

        $queue = $layersIn->map(fn ($l) => [
            'qty' => (int) $l->quantity,
            'price' => (float) $l->unit_price,
            'date' => $l->movement_date,
        ])->all();

        $remaining = (int) $totalOut;
        while ($remaining > 0 && count($queue) > 0) {
            $front = &$queue[0];
            if ($front['qty'] <= $remaining) {
                $remaining -= $front['qty'];
                array_shift($queue);
            } else {
                $front['qty'] -= $remaining;
                $remaining = 0;
            }
            unset($front);
        }

        $totalQty = array_sum(array_column($queue, 'qty'));
        $totalValue = array_reduce($queue, fn ($acc, $l) => $acc + $l['qty'] * $l['price'], 0);
        $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

        return [
            'remaining_qty' => $totalQty,
            'total_value' => round($totalValue, 2),
            'avg_cost' => round($avgCost, 2),
            'layers' => $queue,
        ];
    }
}
