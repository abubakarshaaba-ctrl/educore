<?php

use App\Http\Controllers\SchoolSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    'active.account',
    'tenant',
    'tenant.access',
    'tenant.onboarding.complete',
])->get('settings/authorized-signature', [SchoolSettingController::class, 'signatureFile'])
    ->name('settings.authorized-signature');
