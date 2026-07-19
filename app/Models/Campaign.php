<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'description', 'type', 'status', 'channel',
        'filter_tags', 'filter_source', 'filter_city', 'filter_lead_stage',
        'message', 'scheduled_at', 'follow_up_after_days',
        'total_contacts', 'sent_count', 'failed_count',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'filter_tags'       => 'array',
        'filter_lead_stage' => 'array',
        'scheduled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class)->orderBy('step_number');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'scheduled']);
    }

    public function successRate(): float
    {
        if ($this->total_contacts === 0) {
            return 0.0;
        }
        return round(($this->sent_count / $this->total_contacts) * 100, 1);
    }
}
