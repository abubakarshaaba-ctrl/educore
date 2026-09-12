<?php

use App\Http\Controllers\Api\MobilePortalAccountsController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal-accounts')->group(function (): void {
    Route::get('/', [MobilePortalAccountsController::class, 'index']);
    Route::post('students/bulk', [MobilePortalAccountsController::class, 'bulkStudents']);
    Route::post('students/{student}', [MobilePortalAccountsController::class, 'createStudent'])->whereNumber('student');
    Route::post('guardians/{guardian}', [MobilePortalAccountsController::class, 'createGuardian'])->whereNumber('guardian');
    Route::post('users/{portalUser}/reset-password', [MobilePortalAccountsController::class, 'resetPassword'])->whereNumber('portalUser');
    Route::post('users/{portalUser}/toggle', [MobilePortalAccountsController::class, 'toggle'])->whereNumber('portalUser');
});
