<?php

namespace App\Models;

use App\Services\OrderNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'channel', 'user_id', 'contact_id', 'lead_id',
        'customer_name', 'customer_phone', 'customer_email',
        'delivery_address', 'city', 'postcode', 'state', 'landmark',
        'subtotal', 'delivery_fee', 'total',
        'status', 'payment_method', 'payment_status', 'payment_reference', 'payment_screenshot_path',
        'payment_verification_status', 'payment_verified_amount', 'payment_verification_notes',
        'notes', 'admin_notes', 'tracking_number',
        'confirmed_at', 'dispatched_at', 'delivered_at',
    ];

    protected $casts = [
        'subtotal'                 => 'decimal:2',
        'delivery_fee'             => 'decimal:2',
        'total'                    => 'decimal:2',
        'payment_verified_amount'  => 'decimal:2',
        'confirmed_at'             => 'datetime',
        'dispatched_at'            => 'datetime',
        'delivered_at'             => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'MRZ-' . strtoupper(substr(uniqid(), -6));
            }
        });

        static::updated(function (Order $order) {
            if (empty($order->customer_phone)) {
                return;
            }

            $statusChanged  = $order->wasChanged('status') && $order->status !== 'cancelled';
            $paymentReceived = $order->wasChanged('payment_status') && $order->payment_status === 'paid';

            if ($statusChanged || $paymentReceived) {
                app(OrderNotificationService::class)->sendStatusUpdate($order);
            }
        });
    }

    /**
     * The single "what should staff do next" step for this order, derived
     * from status + payment_status. Drives the Next Action button (table
     * row, ViewOrder header) and the ViewOrder "what's next" banner so the
     * flow is defined in exactly one place.
     */
    public function nextAction(): ?array
    {
        if ($this->status === 'cancelled' || $this->status === 'delivered') {
            return null;
        }

        return match (true) {
            $this->status === 'pending' => [
                'key'      => 'confirm',
                'label'    => 'Confirm Order',
                'icon'     => 'heroicon-o-check-circle',
                'color'    => 'info',
                'confirm'  => true,
                'updates'  => ['status' => 'confirmed', 'confirmed_at' => now()],
            ],
            $this->status === 'confirmed' => [
                'key'     => 'prepare',
                'label'   => 'Start Packing',
                'icon'    => 'heroicon-o-cube',
                'color'   => 'primary',
                'confirm' => false,
                'updates' => ['status' => 'preparing'],
            ],
            $this->status === 'preparing' && $this->payment_status === 'unpaid' => [
                'key'     => 'markPaid',
                'label'   => 'Mark Payment Received',
                'icon'    => 'heroicon-o-banknotes',
                'color'   => 'warning',
                'confirm' => true,
                'updates' => ['payment_status' => 'paid'],
            ],
            $this->status === 'preparing' && $this->payment_status === 'paid' => [
                'key'            => 'dispatch',
                'label'          => 'Dispatch Order',
                'icon'           => 'heroicon-o-truck',
                'color'          => 'success',
                'confirm'        => false,
                'trackingForm'   => true,
                'updates'        => ['status' => 'delivering', 'dispatched_at' => now()],
            ],
            $this->status === 'delivering' => [
                'key'     => 'deliver',
                'label'   => 'Mark Delivered',
                'icon'    => 'heroicon-o-check-badge',
                'color'   => 'success',
                'confirm' => true,
                'updates' => ['status' => 'delivered', 'delivered_at' => now()],
            ],
            default => null,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Recompute subtotal/total from current line items. Called whenever
     * order items change (admin edit, storefront checkout, etc.) so totals
     * never drift from what's actually in the order.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');

        $this->forceFill([
            'subtotal' => $subtotal,
            'total'    => $subtotal + $this->delivery_fee,
        ])->saveQuietly();
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
