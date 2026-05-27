<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'category_id', 'description', 'unit_price', 'min_threshold'];

    protected $appends = ['total_stock', 'is_low_stock'];

    public function getTotalStockAttribute()
    {
        return $this->getTotalStock();
    }

    public function getIsLowStockAttribute()
    {
        return $this->getTotalStock() < $this->min_threshold;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function transferItems()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function getStockAtWarehouse($warehouseId)
    {
        $in = $this->stockMovements()->where('warehouse_id', $warehouseId)->where('type', 'IN')->sum('quantity');
        $out = $this->stockMovements()->where('warehouse_id', $warehouseId)->where('type', 'OUT')->sum('quantity');
        return $in - $out;
    }

    public function getTotalStock()
    {
        $in = $this->stockMovements()->where('type', 'IN')->sum('quantity');
        $out = $this->stockMovements()->where('type', 'OUT')->sum('quantity');
        return $in - $out;
    }

    /**
     * Calculate stock value using FIFO method
     */
    public function getInventoryValue($method = 'FIFO')
    {
        $movements = $this->stockMovements()
            ->where('type', 'IN')
            ->orderBy('created_at', $method === 'FIFO' ? 'asc' : 'desc')
            ->get();

        $totalOut = $this->stockMovements()->where('type', 'OUT')->sum('quantity');
        $value = 0;
        $remainingOut = $totalOut;

        foreach ($movements as $move) {
            if ($remainingOut >= $move->quantity) {
                $remainingOut -= $move->quantity;
            } else {
                $available = $move->quantity - $remainingOut;
                $value += $available * $this->unit_price; // Simplification: using current unit_price
                $remainingOut = 0;
            }
        }

        return $value;
    }
}
