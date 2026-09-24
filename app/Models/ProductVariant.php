<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'price', 'free_gift_label', 'free_gift_weight_kg', 'weight_value', 'weight_unit', 'shipping_weight_kg',
        'stock_qty', 'low_stock_threshold', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight_value' => 'decimal:3',
        'shipping_weight_kg' => 'decimal:3',
        'free_gift_weight_kg' => 'decimal:3',
        'is_active' => 'boolean',
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

    public function getWeightInKgAttribute(): ?float
    {
        if (! $this->weight_value || ! in_array($this->weight_unit, ['kg', 'g'], true)) {
            return null;
        }

        return $this->weight_unit === 'g' ? (float) $this->weight_value / 1000 : (float) $this->weight_value;
    }

    /**
     * Physical weight used by the courier calculator. A dedicated override is
     * useful for piece/box variants and products whose packed weight differs
     * from the customer-facing net weight.
     */
    public function getShippingWeightInKgAttribute(): float
    {
        if ((float) $this->shipping_weight_kg > 0) {
            return (float) $this->shipping_weight_kg;
        }

        return (float) ($this->weight_in_kg ?? 0);
    }

    public function getPricePerKgAttribute(): ?float
    {
        $weightInKg = $this->weight_in_kg;

        return $weightInKg > 0 ? (float) $this->price / $weightInKg : null;
    }
}
