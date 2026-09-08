<?php

use App\Http\Controllers\ReportCardComputeController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;

// Registered after routes/web.php so this single route replaces the legacy
// ReportCardController::compute handler while retaining the same web security
// boundary and route name. Preview, PDFs, remarks and publication routes stay
// on the existing ReportCardController.
Route::middleware([
    'web',
    'auth',
    'active.account',
    'tenant',
    'tenant.access',
    'tenant.onboarding.complete',
    StaffOnly::class,
    CheckModuleAccess::class,
])->post('reports/compute', ReportCardComputeController::class)
    ->name('reports.compute');
