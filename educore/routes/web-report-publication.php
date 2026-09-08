<?php

use App\Http\Controllers\ReportCardPublicationController;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;

// Registered after routes/web.php so these handlers replace only the legacy
// publication mutations while preserving the existing URLs, route names and
// authenticated tenant/staff/module-access boundary.
Route::middleware([
    'web',
    'auth',
    'active.account',
    'tenant',
    'tenant.access',
    'tenant.onboarding.complete',
    StaffOnly::class,
    CheckModuleAccess::class,
])->group(function (): void {
    Route::post('reports/publish', [ReportCardPublicationController::class, 'publish'])
        ->name('reports.publish');
    Route::post('reports/unpublish', [ReportCardPublicationController::class, 'unpublish'])
        ->name('reports.unpublish');
});
