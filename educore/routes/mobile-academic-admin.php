<?php

use App\Http\Controllers\Api\MobileAcademicCycleController;
use App\Http\Controllers\Api\MobileCurriculumController;
use App\Http\Controllers\Api\MobileSubjectsController;
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

Route::prefix('curriculum')->group(function (): void {
    Route::get('/', [MobileCurriculumController::class, 'index']);
    Route::post('tracks', [MobileCurriculumController::class, 'storeTrack']);
    Route::patch('tracks/{track}', [MobileCurriculumController::class, 'updateTrack'])->whereNumber('track');
    Route::delete('tracks/{track}', [MobileCurriculumController::class, 'destroyTrack'])->whereNumber('track');
    Route::post('rules', [MobileCurriculumController::class, 'storeRule']);
    Route::patch('rules/{rule}', [MobileCurriculumController::class, 'updateRule'])->whereNumber('rule');
    Route::delete('rules/{rule}', [MobileCurriculumController::class, 'destroyRule'])->whereNumber('rule');
});

Route::prefix('subjects')->group(function (): void {
    Route::get('/', [MobileSubjectsController::class, 'index']);
    Route::post('/', [MobileSubjectsController::class, 'store']);
    Route::patch('{subject}', [MobileSubjectsController::class, 'update'])->whereNumber('subject');
    Route::delete('{subject}', [MobileSubjectsController::class, 'destroy'])->whereNumber('subject');
});
