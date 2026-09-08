<?php

use App\Http\Controllers\Api\MobileTransfersController;
use Illuminate\Support\Facades\Route;

Route::prefix('transfers')->group(function (): void {
    Route::get('/', [MobileTransfersController::class, 'index']);
    Route::post('cross-school', [MobileTransfersController::class, 'requestCrossSchool']);
    Route::post('cross-school/{transfer}/approve', [MobileTransfersController::class, 'approveCrossSchool'])
        ->whereNumber('transfer');
    Route::post('cross-school/{transfer}/reject', [MobileTransfersController::class, 'rejectCrossSchool'])
        ->whereNumber('transfer');
});
