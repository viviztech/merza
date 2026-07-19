<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name', 'match_type', 'match_values', 'rate_per_kg', 'eta_days', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'match_values' => 'array',
        'rate_per_kg'  => 'float',
        'eta_days'     => 'integer',
        'is_active'    => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
