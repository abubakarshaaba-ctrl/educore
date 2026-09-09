<?php

use App\Http\Controllers\PlatformImpersonationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account'])
    ->prefix('super')
    ->name('super.')
    ->group(function (): void {
        Route::post('impersonate/{tenant}', [PlatformImpersonationController::class, 'start'])
            ->whereNumber('tenant')
            ->name('impersonate');
        Route::post('stop-impersonating', [PlatformImpersonationController::class, 'stop'])
            ->name('stop-impersonating');
    });
