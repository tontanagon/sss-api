<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:update-status-overdue')->cron('0 0 * * *')->withoutOverlapping(); // at midnight 00:00
Schedule::command('app:send-notification-overdue')->cron('0 2 * * *')->withoutOverlapping(); // at midnight 02:00

// Schedule::command('app:update-status-overdue')->cron('* * * * *')->withoutOverlapping(); // at midnight 00:00
// Schedule::command('app:send-notification-overdue')->cron('* * * * *')->withoutOverlapping(); // at midnight 02:00