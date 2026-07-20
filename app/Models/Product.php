<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'harvest_date', 'farm_location', 'sweetness_level', 'delivery_time',
        'base_price', 'unit', 'is_active', 'is_featured', 'is_available_today', 'sort_order',
    ];

    protected $casts = [
        'base_price'         => 'decimal:2',
        'harvest_date'       => 'date',
        'is_active'          => 'boolean',
        'is_featured'        => 'boolean',
        'is_available_today' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $disk = config('media-library.disk_name', 'r2');

        $this->addMediaCollection('images')
             ->useDisk($disk)
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
             ->withResponsiveImages();

        $this->addMediaCollection('thumbnail')
             ->useDisk($disk)
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(400)
             ->height(400)
             ->sharpen(10);

        $this->addMediaConversion('card')
             ->width(800)
             ->height(600);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->approved();
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('thumbnail', 'thumb')
            ?: $this->getFirstMediaUrl('images', 'thumb')
            ?: asset('images/placeholder-product.png');
    }

    public function getInStockAttribute(): bool
    {
        return $this->variants()->where('stock_qty', '>', 0)->exists();
    }
}
