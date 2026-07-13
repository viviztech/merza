<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'assigned_to', 'name', 'phone', 'email', 'source',
        'tags', 'notes', 'city', 'state',
        'is_customer', 'is_blocked', 'last_contacted_at',
        'wa_opted_out', 'wa_opted_out_at',
    ];

    protected $casts = [
        'tags'               => 'array',
        'is_customer'        => 'boolean',
        'is_blocked'         => 'boolean',
        'wa_opted_out'       => 'boolean',
        'last_contacted_at'  => 'datetime',
        'wa_opted_out_at'    => 'datetime',
    ];

    public function optOutWhatsApp(): void
    {
        $this->update([
            'wa_opted_out'    => true,
            'wa_opted_out_at' => now(),
        ]);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->latest('sent_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function campaignContacts(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }

    public function getActiveleadAttribute(): ?Lead
    {
        return $this->leads()->whereNotIn('stage', ['converted', 'lost'])->latest()->first();
    }

    public function getWhatsappUrlAttribute(): string
    {
        $phone = preg_replace('/\D/', '', $this->phone);
        return "https://wa.me/{$phone}";
    }
}
