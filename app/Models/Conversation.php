<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'contact_id', 'handled_by', 'channel', 'direction',
        'message', 'media_url', 'status', 'is_bot', 'sent_at',
        'wa_message_id', 'replied_to_id',
    ];

    protected $casts = [
        'is_bot'  => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function repliedTo(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'replied_to_id');
    }

    public function isDraft(): bool
    {
        return $this->is_bot && is_null($this->sent_at);
    }
}
