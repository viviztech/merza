<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignStep extends Model
{
    protected $fillable = [
        'campaign_id', 'step_number', 'delay_days', 'message',
    ];

    protected static function booted(): void
    {
        static::creating(function (CampaignStep $step) {
            if (empty($step->step_number)) {
                $max = static::where('campaign_id', $step->campaign_id)->max('step_number') ?? 0;
                $step->step_number = $max + 1;
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
