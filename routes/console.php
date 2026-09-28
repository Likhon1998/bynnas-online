<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Midnight counter auto-close (Asia/Dhaka)
|--------------------------------------------------------------------------
| Closes every open till with the system expected drawer cash so the next
| calendar day starts clean. Staff must open a new session after midnight.
|
| Keep the scheduler running (Windows Task Scheduler / cron):
|   php artisan schedule:work
| or every minute:
|   php artisan schedule:run
*/
if (retail_enabled()) {
    Schedule::command('counters:auto-close-sessions')
        ->dailyAt('00:00')
        ->timezone(config('app.display_timezone', config('app.timezone', 'Asia/Dhaka')))
        ->withoutOverlapping();
}
