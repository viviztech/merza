<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'gtm_container_id', 'meta_pixel_id', 'facebook_domain_verification',
        'custom_head_scripts', 'custom_body_scripts',
        'order_webhook_url', 'order_webhook_secret',
    ];

    protected $hidden = [
        'order_webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'order_webhook_secret' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
}
