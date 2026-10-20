<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id',
        'event_id',
        'topic',
        'status',
        'response_status',
        'attempts',
        'payload_hash',
        'request_body',
        'response_body',
        'response_headers',
        'error',
        'last_attempted_at',
        'locked_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'response_headers' => 'array',
            'last_attempted_at' => 'datetime',
            'locked_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
