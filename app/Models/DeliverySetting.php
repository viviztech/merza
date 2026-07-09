<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = [
        'packing_charge', 'packing_weight_kg', 'free_weight_threshold_kg', 'free_weight_kg', 'is_active',
    ];

    protected $casts = [
        'packing_charge'           => 'float',
        'packing_weight_kg'        => 'float',
        'free_weight_threshold_kg' => 'float',
        'free_weight_kg'           => 'float',
        'is_active'                => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'packing_charge'           => 50,
            'packing_weight_kg'        => 1,
            'free_weight_threshold_kg' => 5,
            'free_weight_kg'           => 1,
            'is_active'                => true,
        ]);
    }
}
