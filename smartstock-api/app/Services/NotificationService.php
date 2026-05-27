<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;

class NotificationService
{
    public function send(User $user, string $type, string $title, string $message, string $severity = 'INFO', array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function broadcast(array $userIds, string $type, string $title, string $message, string $severity = 'INFO', array $data = []): int
    {
        $now = now();
        $rows = array_map(fn ($id) => [
            'user_id' => $id,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        if (empty($rows)) return 0;
        Notification::insert($rows);
        return count($rows);
    }

    public function createLowStockAlert(Product $product, int $warehouseId, int $currentQty): void
    {
        $warehouse = Warehouse::find($warehouseId);
        if (! $warehouse) return;

        // Dedup: check existing unread alert dalam 1 jam terakhir
        $exists = Notification::where('type', Notification::TYPE_STOCK_ALERT)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subHour())
            ->whereJsonContains('data->product_id', $product->id)
            ->whereJsonContains('data->warehouse_id', $warehouseId)
            ->exists();
        if ($exists) return;

        $severity = match (true) {
            $currentQty === 0 => Notification::SEV_CRITICAL,
            $currentQty <= ($product->min_stock * 0.5) => Notification::SEV_CRITICAL,
            default => Notification::SEV_WARNING,
        };

        $title = $currentQty === 0
            ? "STOK HABIS: {$product->name}"
            : "Stok Menipis: {$product->name}";

        $message = "Stok {$product->name} ({$product->sku}) di gudang {$warehouse->name} tersisa {$currentQty} (min: {$product->min_stock})";

        $recipients = User::where('is_active', true)
            ->whereHas('role', function ($q) use ($warehouseId) {
                $q->whereIn('code', [Role::ADMIN, Role::MANAGER]);
            })
            ->orWhere(function ($q) use ($warehouseId) {
                $q->where('is_active', true)
                  ->where('warehouse_id', $warehouseId)
                  ->whereHas('role', fn ($r) => $r->where('code', Role::STAFF));
            })
            ->pluck('id')->all();

        $this->broadcast(
            $recipients,
            Notification::TYPE_STOCK_ALERT,
            $title,
            $message,
            $severity,
            [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse->name,
                'current_qty' => $currentQty,
                'min_stock' => $product->min_stock,
            ],
        );
    }

    public function resolveStockAlerts(int $productId, int $warehouseId): void
    {
        Notification::where('type', Notification::TYPE_STOCK_ALERT)
            ->whereNull('read_at')
            ->whereJsonContains('data->product_id', $productId)
            ->whereJsonContains('data->warehouse_id', $warehouseId)
            ->update(['read_at' => now()]);
    }
}
