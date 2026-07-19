<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_variant_id',
        'product_name', 'variant_name', 'free_gift_label', 'free_gift_weight_kg', 'sku',
        'quantity', 'unit_price', 'subtotal',
    ];

    protected $casts = [
        'unit_price'           => 'decimal:2',
        'subtotal'             => 'decimal:2',
        'free_gift_weight_kg'  => 'decimal:3',
    ];

    protected static function booted(): void
    {
        // Keep the parent order's subtotal/total in sync whenever its items
        // change — covers storefront checkout, admin creation, and inline
        // editing on the Order edit screen alike.
        static::saved(fn (OrderItem $item) => $item->order?->recalculateTotals());
        static::deleted(fn (OrderItem $item) => $item->order?->recalculateTotals());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
