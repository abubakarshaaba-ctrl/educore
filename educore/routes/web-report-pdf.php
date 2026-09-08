<?php

use App\Http\Controllers\ReportCardPdfController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;

// Registered after routes/web.php so the existing staff PDF URL and route name
// remain stable while rendering moves to the shared, read-only document service.
Route::middleware([
    'web',
    'auth',
    'active.account',
    'tenant',
    'tenant.access',
    'tenant.onboarding.complete',
    StaffOnly::class,
    CheckModuleAccess::class,
])->match(['get', 'post'], 'reports/pdf/{student}', ReportCardPdfController::class)
    ->name('reports.pdf');
