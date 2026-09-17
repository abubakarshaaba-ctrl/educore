<?php

use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function (): void {
        Route::get('system-health', [SystemHealthController::class, 'index'])
            ->name('system-health');
    });
