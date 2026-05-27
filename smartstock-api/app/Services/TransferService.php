<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ProductWarehouseStock;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\TransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        private StockService $stocks,
        private NotificationService $notifications,
    ) {}

    /**
     * Buat transfer + reserve stok. Pseudocode di Tahap 9.5.
     */
    public function createTransfer(int $fromWh, int $toWh, array $items, User $requester, ?string $reason = null): StockTransfer
    {
        if ($fromWh === $toWh) {
            throw new \DomainException('Gudang asal dan tujuan tidak boleh sama');
        }
        if (count($items) === 0) {
            throw new \DomainException('Items tidak boleh kosong');
        }

        return DB::transaction(function () use ($fromWh, $toWh, $items, $requester, $reason) {
            $productIds = array_map(fn ($i) => (int) $i['product_id'], $items);
            sort($productIds); // hindari deadlock dengan urutan konsisten

            $stocks = ProductWarehouseStock::where('warehouse_id', $fromWh)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()->keyBy('product_id');

            foreach ($items as $item) {
                $stock = $stocks->get($item['product_id']);
                $available = $stock ? $stock->quantity - $stock->reserved_quantity : 0;
                if ($available < $item['quantity']) {
                    throw new \DomainException("Stok produk #{$item['product_id']} tidak cukup. Tersedia: {$available}, diminta: {$item['quantity']}");
                }
            }

            foreach ($items as $item) {
                ProductWarehouseStock::where('warehouse_id', $fromWh)
                    ->where('product_id', $item['product_id'])
                    ->increment('reserved_quantity', $item['quantity']);
            }

            $code = 'TRF-'.now()->format('Ymd').'-'.str_pad((string) (StockTransfer::count() + 1), 4, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'transfer_code' => $code,
                'from_warehouse_id' => $fromWh,
                'to_warehouse_id' => $toWh,
                'requested_by' => $requester->id,
                'status' => StockTransfer::STATUS_PENDING,
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            AuditLog::record('TRANSFER_CREATED', $transfer);

            return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function approve(StockTransfer $transfer, User $approver): StockTransfer
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            throw new \DomainException('Hanya transfer status PENDING yang bisa di-approve');
        }

        DB::transaction(function () use ($transfer, $approver) {
            $items = $transfer->items()->lockForUpdate()->get();
            $productIds = $items->pluck('product_id')->sort()->values()->all();

            $stocks = ProductWarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()->keyBy('product_id');

            foreach ($items as $item) {
                $stock = $stocks->get($item->product_id);
                $stock->update([
                    'quantity' => $stock->quantity - $item->quantity,
                    'reserved_quantity' => $stock->reserved_quantity - $item->quantity,
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'user_id' => $approver->id,
                    'type' => StockMovement::TYPE_TRANSFER_OUT,
                    'quantity' => $item->quantity,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'movement_date' => now(),
                    'note' => "Transfer {$transfer->transfer_code} ke gudang ID {$transfer->to_warehouse_id}",
                ]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditLog::record('TRANSFER_APPROVED', $transfer);
        });

        // Notif staf asal
        $requester = User::find($transfer->requested_by);
        if ($requester) {
            $this->notifications->send(
                $requester,
                'TRANSFER',
                "Transfer {$transfer->transfer_code} disetujui",
                "Transfer Anda telah disetujui oleh {$approver->name}.",
                'INFO',
                ['transfer_id' => $transfer->id],
            );
        }

        return $transfer->fresh()->load(['items.product', 'fromWarehouse', 'toWarehouse', 'requester', 'approver']);
    }

    public function reject(StockTransfer $transfer, User $approver, string $rejectNote): StockTransfer
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            throw new \DomainException('Hanya transfer status PENDING yang bisa di-reject');
        }

        DB::transaction(function () use ($transfer, $approver, $rejectNote) {
            foreach ($transfer->items as $item) {
                ProductWarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('reserved_quantity', $item->quantity);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_REJECTED,
                'approved_by' => $approver->id,
                'reject_note' => $rejectNote,
                'approved_at' => now(),
            ]);

            AuditLog::record('TRANSFER_REJECTED', $transfer);
        });

        $requester = User::find($transfer->requested_by);
        if ($requester) {
            $this->notifications->send(
                $requester,
                'TRANSFER',
                "Transfer {$transfer->transfer_code} ditolak",
                "Alasan: {$rejectNote}",
                'WARNING',
                ['transfer_id' => $transfer->id],
            );
        }

        return $transfer->fresh();
    }

    public function ship(StockTransfer $transfer, User $user): StockTransfer
    {
        if ($transfer->status !== StockTransfer::STATUS_APPROVED) {
            throw new \DomainException('Transfer harus berstatus APPROVED');
        }
        $transfer->update([
            'status' => StockTransfer::STATUS_SHIPPED,
            'shipped_at' => now(),
        ]);
        AuditLog::record('TRANSFER_SHIPPED', $transfer);
        return $transfer->fresh();
    }

    public function receive(StockTransfer $transfer, User $receiver): StockTransfer
    {
        if (! in_array($transfer->status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_SHIPPED], true)) {
            throw new \DomainException('Transfer harus APPROVED atau SHIPPED untuk diterima');
        }

        DB::transaction(function () use ($transfer, $receiver) {
            $items = $transfer->items()->lockForUpdate()->get();
            $productIds = $items->pluck('product_id')->sort()->values()->all();

            foreach ($items as $item) {
                $stock = ProductWarehouseStock::firstOrCreate(
                    ['product_id' => $item->product_id, 'warehouse_id' => $transfer->to_warehouse_id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $stock->lockForUpdate();
                $stock->increment('quantity', $item->quantity);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'user_id' => $receiver->id,
                    'type' => StockMovement::TYPE_TRANSFER_IN,
                    'quantity' => $item->quantity,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'movement_date' => now(),
                    'note' => "Transfer {$transfer->transfer_code} dari gudang ID {$transfer->from_warehouse_id}",
                ]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => $receiver->id,
                'received_at' => now(),
            ]);

            AuditLog::record('TRANSFER_RECEIVED', $transfer);
        });

        return $transfer->fresh()->load(['items.product']);
    }

    public function cancel(StockTransfer $transfer, User $user): StockTransfer
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            throw new \DomainException('Hanya transfer status PENDING yang bisa dibatalkan');
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                ProductWarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('reserved_quantity', $item->quantity);
            }
            $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);
            AuditLog::record('TRANSFER_CANCELLED', $transfer);
        });

        return $transfer->fresh();
    }
}
