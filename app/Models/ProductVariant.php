<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'price', 'free_gift_label', 'free_gift_weight_kg', 'weight_value', 'weight_unit',
        'stock_qty', 'low_stock_threshold', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'                => 'decimal:2',
        'weight_value'         => 'decimal:3',
        'free_gift_weight_kg'  => 'decimal:3',
        'is_active'            => 'boolean',
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

    public function getPricePerKgAttribute(): ?float
    {
        if (! $this->weight_value || ! in_array($this->weight_unit, ['kg', 'g'], true)) {
            return null;
        }

        $weightInKg = $this->weight_unit === 'g' ? $this->weight_value / 1000 : (float) $this->weight_value;

        return $weightInKg > 0 ? (float) $this->price / $weightInKg : null;
    }
}
