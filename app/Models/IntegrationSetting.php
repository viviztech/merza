<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'gtm_container_id', 'meta_pixel_id', 'facebook_domain_verification',
        'custom_head_scripts', 'custom_body_scripts',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
}
