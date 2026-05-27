<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'description', 'category_id',
        'unit', 'min_stock', 'price_buy', 'price_sell', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_buy' => 'decimal:2',
        'price_sell' => 'decimal:2',
        'min_stock' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse_stocks')
            ->withPivot('quantity', 'reserved_quantity')
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    public function getAvailableStockAttribute(): int
    {
        return (int) $this->stocks()->sum(\DB::raw('quantity - reserved_quantity'));
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock <= $this->min_stock;
    }
}
