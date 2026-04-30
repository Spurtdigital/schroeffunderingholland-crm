<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync Moneybird quote statuses every 15 minutes
Schedule::command('sync:moneybird-quotes')
    ->everyFifteenMinutes()
    ->runInBackground();

// Sync Moneybird contacts cache every hour
Schedule::command('sync:moneybird-contacts-cache')
    ->hourly()
    ->runInBackground();
