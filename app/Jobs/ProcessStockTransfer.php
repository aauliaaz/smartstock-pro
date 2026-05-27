<?php

namespace App\Jobs;

use App\Models\StockTransfer;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessStockTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transfer;
    protected $approverId;

    public function __construct(StockTransfer $transfer, $approverId)
    {
        $this->transfer = $transfer;
        $this->approverId = $approverId;
    }

    public function handle(): void
    {
        DB::transaction(function() {
            foreach ($this->transfer->items as $item) {
                // Out from origin
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $this->transfer->from_warehouse_id,
                    'user_id' => $this->approverId,
                    'quantity' => $item->quantity,
                    'type' => 'OUT',
                    'reference' => $this->transfer->transfer_number,
                    'notes' => 'Stock Transfer (Origin)'
                ]);

                // In to destination
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $this->transfer->to_warehouse_id,
                    'user_id' => $this->approverId,
                    'quantity' => $item->quantity,
                    'type' => 'IN',
                    'reference' => $this->transfer->transfer_number,
                    'notes' => 'Stock Transfer (Destination)'
                ]);
            }

            $this->transfer->update([
                'status' => 'COMPLETED',
                'approved_at' => now(),
                'approved_by' => $this->approverId
            ]);
        });
    }
}
