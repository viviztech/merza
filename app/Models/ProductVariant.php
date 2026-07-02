<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'price', 'weight_value', 'weight_unit',
        'stock_qty', 'low_stock_threshold', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'weight_value' => 'decimal:3',
        'is_active'    => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLowStockAttribute(): bool
    {
        return $this->stock_qty > 0 && $this->stock_qty <= $this->low_stock_threshold;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->weight_value
            ? "{$this->name} ({$this->weight_value}{$this->weight_unit})"
            : $this->name;
    }
}
