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

    Route::post('intra-class', [MobileTransfersController::class, 'requestIntraClass']);
    Route::post('intra-class/{transfer}/approve', [MobileTransfersController::class, 'approveIntraClass'])
        ->whereNumber('transfer');
    Route::post('intra-class/{transfer}/reject', [MobileTransfersController::class, 'rejectIntraClass'])
        ->whereNumber('transfer');
    Route::post('intra-class/{transfer}/cancel', [MobileTransfersController::class, 'cancelIntraClass'])
        ->whereNumber('transfer');

    Route::post('interclass', [MobileTransfersController::class, 'requestInterclass']);
    Route::post('interclass/{transfer}/approve', [MobileTransfersController::class, 'approveInterclass'])
        ->whereNumber('transfer');
    Route::post('interclass/{transfer}/reject', [MobileTransfersController::class, 'rejectInterclass'])
        ->whereNumber('transfer');
    Route::post('interclass/{transfer}/cancel', [MobileTransfersController::class, 'cancelInterclass'])
        ->whereNumber('transfer');
});
