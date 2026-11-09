<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kiểm tra mất tín hiệu IoT mỗi 5 phút
Schedule::command('iot:check-signal-loss')->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

