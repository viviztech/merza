<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    public const TOPICS = [
        'customer.created' => 'Customer created',
        'customer.updated' => 'Customer updated',
        'cart.added' => 'Cart item added',
        'checkout.started' => 'Checkout started',
        'order.created' => 'Order created',
        'order.updated' => 'Order updated',
    ];

    protected $fillable = [
        'created_by',
        'name',
        'status',
        'topic',
        'delivery_url',
        'secret',
        'api_version',
        'last_delivered_at',
        'last_response_status',
        'last_error',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }
}
