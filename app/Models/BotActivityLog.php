<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotActivityLog extends Model
{
    protected $fillable = [
        'event_type', 'meta_lead_id', 'meta_form_id', 'meta_page_id',
        'contact_id', 'lead_id', 'conversation_id',
        'raw_payload', 'generated_message', 'error_message', 'status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public static array $eventLabels = [
        'webhook_received'    => 'Webhook Received',
        'lead_fetched'        => 'Lead Fetched',
        'contact_created'     => 'Contact Created',
        'contact_updated'     => 'Contact Updated',
        'lead_created'        => 'Lead Created',
        'message_generated'   => 'Message Generated',
        'conversation_created'=> 'Conversation Created',
        'error'               => 'Error',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
