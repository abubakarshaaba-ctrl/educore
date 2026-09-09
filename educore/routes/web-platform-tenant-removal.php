<?php

use App\Http\Controllers\PlatformTenantRemovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function (): void {
        Route::delete('tenants/{tenant}', [PlatformTenantRemovalController::class, 'destroy'])
            ->whereNumber('tenant')
            ->name('tenant.destroy');
    });
