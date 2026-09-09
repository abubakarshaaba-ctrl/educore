<?php

use App\Http\Controllers\PlatformBillingMutationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function (): void {
        Route::post('billing/generate', [PlatformBillingMutationController::class, 'generate'])->name('billing.generate');
        Route::post('billing/{invoice}/paid', [PlatformBillingMutationController::class, 'paid'])->whereNumber('invoice')->name('billing.paid');
    });
