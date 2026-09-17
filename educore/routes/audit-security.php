<?php

use App\Http\Controllers\AuditSecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
    ->get('super/audit-security', [AuditSecurityController::class, 'index'])
    ->name('super.audit-security');
