<?php

use App\Console\Commands\AcademicRollover;
use App\Console\Commands\BackupDatabase;
use App\Console\Commands\InspectAcademicCycle;
use App\Console\Commands\RepairAcademicCurrentState;
use App\Console\Commands\RepairStudentEnrollments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

app(ConsoleKernel::class)->registerCommand(app(RepairStudentEnrollments::class));
app(ConsoleKernel::class)->registerCommand(app(InspectAcademicCycle::class));
app(ConsoleKernel::class)->registerCommand(app(AcademicRollover::class));
app(ConsoleKernel::class)->registerCommand(app(RepairAcademicCurrentState::class));
app(ConsoleKernel::class)->registerCommand(app(BackupDatabase::class));

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

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
