<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConsent extends Model
{
    protected $table = 'whatsapp_consents';

    protected $fillable = [
        'contact_id', 'recorded_by', 'category', 'source', 'evidence',
        'granted_at', 'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
