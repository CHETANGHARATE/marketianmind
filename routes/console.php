<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('engagement:process')
    ->daily()
    ->name('process-student-engagement-automation')
    ->withoutOverlapping();

Schedule::command('automation:process')
    ->everyFifteenMinutes()
    ->name('process-marketing-automation-executions')
    ->withoutOverlapping();

Schedule::command('app:backup --type=db --prune')
    ->dailyAt('02:00')
    ->name('daily-database-backup')
    ->withoutOverlapping();

Schedule::command('app:backup --type=files --prune')
    ->weeklyOn(0, '03:00')
    ->name('weekly-files-backup')
    ->withoutOverlapping();

Schedule::command('enrollments:check-expiry')
    ->daily()
    ->name('check-enrollment-expiry')
    ->withoutOverlapping();

Schedule::command('enrollments:send-expiry-notifications')
    ->dailyAt('08:00')
    ->name('send-course-expiry-notifications')
    ->withoutOverlapping();

Schedule::command('retention:process-support')
    ->dailyAt('09:00')
    ->name('process-student-retention-support')
    ->withoutOverlapping();
