<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'contact_id', 'assigned_to', 'stage', 'source',
        'product_interest', 'estimated_value', 'notes',
        'due_at', 'converted_at',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'due_at'          => 'datetime',
        'converted_at'    => 'datetime',
    ];

    public static array $stages = [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'interested'=> 'Interested',
        'quoted'    => 'Quoted',
        'converted' => 'Converted',
        'lost'      => 'Lost',
    ];

    public static array $stageColors = [
        'new'       => 'gray',
        'contacted' => 'info',
        'interested'=> 'warning',
        'quoted'    => 'primary',
        'converted' => 'success',
        'lost'      => 'danger',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getStageLabelAttribute(): string
    {
        return self::$stages[$this->stage] ?? $this->stage;
    }

    public function getStageColorAttribute(): string
    {
        return self::$stageColors[$this->stage] ?? 'gray';
    }
}
