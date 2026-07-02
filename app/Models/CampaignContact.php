<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignContact extends Model
{
    protected $fillable = [
        'campaign_id', 'contact_id', 'status', 'current_step',
        'next_send_at', 'last_sent_at', 'conversation_id',
    ];

    protected $casts = [
        'next_send_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
