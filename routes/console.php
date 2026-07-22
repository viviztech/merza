<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every minute — processes scheduled broadcasts, drip step advances, and follow-up triggers
Schedule::command('campaigns:process')->everyMinute()->withoutOverlapping();

// Every 5 minutes — one reminder each for carts stalled mid-checkout, and a
// follow-up on WhatsApp orders left unpaid with no reference/screenshot.
Schedule::command('whatsapp:nudge-stalled-checkouts')->everyFiveMinutes()->withoutOverlapping();
