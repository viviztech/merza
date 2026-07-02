<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every minute — processes scheduled broadcasts, drip step advances, and follow-up triggers
Schedule::command('campaigns:process')->everyMinute()->withoutOverlapping();
