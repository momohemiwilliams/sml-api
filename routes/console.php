<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Security schedule jobs (requires `php artisan schedule:run` every minute on the server)
Schedule::command('securities:process-amortisation')
    ->dailyAt(config('security_events.run_at'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('securities:send-call-reminders')
    ->dailyAt(config('security_events.run_at'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('securities:send-reset-reminders')
    ->dailyAt(config('security_events.run_at'))
    ->withoutOverlapping()
    ->onOneServer();
