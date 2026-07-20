<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'customer_name', 'rating', 'comment', 'photo_path', 'video_url', 'is_approved',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_approved' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk(config('media-library.disk_name', 'r2'))
            ->url($this->photo_path);
    }
}
