<?php

use App\Http\Controllers\TenantOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'super.admin'])
    ->get('super/tenant-operations', [TenantOperationsController::class, 'index'])
    ->name('super.tenant-operations');
