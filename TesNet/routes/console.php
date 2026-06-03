<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tesnet:backup-database')
    ->dailyAt('02:30')
    ->when(fn () => config('tesnet.backup.enabled', true))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

Schedule::command('tesnet:monitor')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/monitor.log'));

Schedule::command('tesnet:sync-package-usage')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/package-usage.log'));
