<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'channel', 'user_id', 'contact_id',
        'customer_name', 'customer_phone', 'customer_email',
        'delivery_address', 'city', 'postcode', 'state',
        'subtotal', 'delivery_fee', 'total',
        'status', 'payment_method', 'payment_status', 'payment_reference', 'payment_screenshot_path',
        'notes', 'admin_notes', 'tracking_number',
        'confirmed_at', 'dispatched_at', 'delivered_at',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'delivery_fee'  => 'decimal:2',
        'total'         => 'decimal:2',
        'confirmed_at'  => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'MRZ-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'warning',
            'confirmed'  => 'info',
            'preparing'  => 'primary',
            'delivering' => 'success',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            default      => 'gray',
        };
    }

    public function getChannelBadgeColorAttribute(): string
    {
        return match ($this->channel) {
            'website'  => 'gray',
            'whatsapp' => 'success',
            'manual'   => 'info',
            default    => 'gray',
        };
    }

    public function getPaymentScreenshotUrlAttribute(): ?string
    {
        if (empty($this->payment_screenshot_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk(config('media-library.disk_name', 'r2'))
            ->url($this->payment_screenshot_path);
    }
}
