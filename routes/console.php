<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('implementation-orders:expire-abandoned')->hourly();
Schedule::command('affiliate:reconcile')->dailyAt('02:30');
Schedule::command('sync:check')->twiceDailyAt(1, 13, 30)->withoutOverlapping();
