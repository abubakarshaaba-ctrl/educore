<?php

use App\Http\Controllers\MailHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/tools/mail-health', [MailHealthController::class, 'check'])
    ->middleware('throttle:3,10')
    ->name('tools.mail-health');
