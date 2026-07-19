<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'customer_name', 'location', 'rating', 'quote', 'product_tag', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'rating'    => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
