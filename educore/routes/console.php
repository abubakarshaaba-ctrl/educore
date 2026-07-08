<?php

use App\Console\Commands\AcademicRollover;
use App\Console\Commands\BackupDatabase;
use App\Console\Commands\InspectAcademicCycle;
use App\Console\Commands\RepairAcademicCurrentState;
use App\Console\Commands\RepairStudentEnrollments;
use App\Console\Commands\SendFeeDefaulterReminders;
use App\Console\Commands\SendSubscriptionRenewalReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

app(ConsoleKernel::class)->registerCommand(app(RepairStudentEnrollments::class));
app(ConsoleKernel::class)->registerCommand(app(InspectAcademicCycle::class));
app(ConsoleKernel::class)->registerCommand(app(AcademicRollover::class));
app(ConsoleKernel::class)->registerCommand(app(RepairAcademicCurrentState::class));
app(ConsoleKernel::class)->registerCommand(app(BackupDatabase::class));
app(ConsoleKernel::class)->registerCommand(app(SendSubscriptionRenewalReminders::class));
app(ConsoleKernel::class)->registerCommand(app(SendFeeDefaulterReminders::class));

// Raw SQL dump to storage/app/backups (lightweight, fast, kept 14 days)
Schedule::command('backup:database')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Spatie backup: full backup with retention management and mail notifications
// Runs 30 min after the raw dump so they don't overlap on disk I/O.
Schedule::command('backup:run --only-db')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('spatie/laravel-backup run failed');
    });

// Weekly cleanup of old spatie backups according to config/backup.php retention policy
Schedule::command('backup:clean')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping();

// Daily: nudge schools whose subscription is about to expire (30/14/7/3/1 days out)
Schedule::command('tenants:send-renewal-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Weekly: nudge parents of students with an outstanding fee balance
Schedule::command('fees:send-defaulter-reminders')
    ->weeklyOn(1, '08:30')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
