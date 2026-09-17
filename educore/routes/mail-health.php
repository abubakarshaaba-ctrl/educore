<?php

use App\Http\Controllers\MailHealthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin', 'throttle:3,10'])
    ->group(function (): void {
        Route::get('/tools/mail-health', [MailHealthController::class, 'check'])
            ->name('tools.mail-health');

        Route::post('/tools/mail-health/test', [MailHealthController::class, 'check'])
            ->name('tools.mail-health.test');
    });
