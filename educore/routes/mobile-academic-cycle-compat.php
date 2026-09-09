<?php

use App\Http\Controllers\Api\MobileAcademicCycleController;
use Illuminate\Support\Facades\Route;

Route::prefix('academic-cycle')->group(function (): void {
    Route::get('/', [MobileAcademicCycleController::class, 'index']);
    Route::post('sessions', [MobileAcademicCycleController::class, 'storeSession']);
    Route::patch('sessions/{session}', [MobileAcademicCycleController::class, 'updateSession'])->whereNumber('session');
    Route::post('sessions/{session}/activate', [MobileAcademicCycleController::class, 'activateSession'])->whereNumber('session');
    Route::get('sessions/{session}/readiness', [MobileAcademicCycleController::class, 'sessionReadiness'])->whereNumber('session');
    Route::post('sessions/{session}/close', [MobileAcademicCycleController::class, 'closeSession'])->whereNumber('session');
    Route::delete('sessions/{session}', [MobileAcademicCycleController::class, 'destroySession'])->whereNumber('session');
    Route::post('terms', [MobileAcademicCycleController::class, 'storeTerm']);
    Route::patch('terms/{term}', [MobileAcademicCycleController::class, 'updateTerm'])->whereNumber('term');
    Route::post('terms/{term}/activate', [MobileAcademicCycleController::class, 'activateTerm'])->whereNumber('term');
    Route::get('terms/{term}/readiness', [MobileAcademicCycleController::class, 'termReadiness'])->whereNumber('term');
    Route::post('terms/{term}/close', [MobileAcademicCycleController::class, 'closeTerm'])->whereNumber('term');
    Route::delete('terms/{term}', [MobileAcademicCycleController::class, 'destroyTerm'])->whereNumber('term');
});
