<?php

use App\Http\Controllers\StaffProfileOnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','throttle:20,1'])->group(function () {
    Route::get('/j/{token}', [StaffProfileOnboardingController::class, 'show'])
        ->where('token', '[A-Za-z0-9]{8,24}')
        ->name('staff.join.show');
    Route::post('/j/{token}', [StaffProfileOnboardingController::class, 'store'])
        ->where('token', '[A-Za-z0-9]{8,24}')
        ->name('staff.join.store');
});

Route::middleware([
    'web','auth','active.account','tenant','tenant.access','tenant.onboarding.complete',
    \App\Http\Middleware\StaffOnly::class,
])->prefix('staff/onboarding')->name('staff.onboarding.')->group(function () {
    Route::get('/', [StaffProfileOnboardingController::class, 'manage'])->name('manage');
    Route::post('/rotate', [StaffProfileOnboardingController::class, 'rotate'])->name('rotate');
    Route::post('/{submission}/approve', [StaffProfileOnboardingController::class, 'approve'])->name('approve');
    Route::post('/{submission}/reject', [StaffProfileOnboardingController::class, 'reject'])->name('reject');
});
