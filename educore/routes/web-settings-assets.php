<?php

use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\StaffAttendanceEvidenceController;
use App\Http\Controllers\StaffIdentityAssetController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    'active.account',
    'tenant',
    'tenant.access',
    'tenant.onboarding.complete',
])->group(function (): void {
    Route::get('settings/authorized-signature', [SchoolSettingController::class, 'signatureFile'])
        ->name('settings.authorized-signature');

    Route::get('staff/{staff}/passport-photo', StaffIdentityAssetController::class)
        ->whereNumber('staff')
        ->name('staff.passport-photo');

    Route::get('staff-attendance/evidence/{record}/{kind}', StaffAttendanceEvidenceController::class)
        ->whereNumber('record')
        ->whereIn('kind', ['passport', 'clock-in', 'proxy'])
        ->name('staff-attendance.evidence');
});
