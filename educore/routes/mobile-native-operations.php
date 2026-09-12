<?php

use App\Http\Controllers\Api\MobileHostelController;
use App\Http\Controllers\Api\MobileInventoryController;
use App\Http\Controllers\Api\MobileLibraryController;
use App\Http\Controllers\Api\MobileRiskController;
use Illuminate\Support\Facades\Route;

Route::prefix('risk')->group(function (): void {
    Route::get('/', [MobileRiskController::class, 'index']);
    Route::post('compute', [MobileRiskController::class, 'compute']);
    Route::put('config', [MobileRiskController::class, 'updateConfig']);
    Route::get('{flag}', [MobileRiskController::class, 'show'])->whereNumber('flag');
    Route::post('{flag}/acknowledge', [MobileRiskController::class, 'acknowledge'])->whereNumber('flag');
    Route::post('{flag}/resolve', [MobileRiskController::class, 'resolve'])->whereNumber('flag');
});

Route::prefix('library')->group(function (): void {
    Route::get('options', [MobileLibraryController::class, 'options']);
    Route::post('loans', [MobileLibraryController::class, 'issue']);
    Route::post('loans/{loan}/return', [MobileLibraryController::class, 'returnLoan'])->whereNumber('loan');
});

Route::prefix('inventory')->group(function (): void {
    Route::get('/', [MobileInventoryController::class, 'index']);
    Route::post('/', [MobileInventoryController::class, 'store']);
    Route::patch('{asset}', [MobileInventoryController::class, 'update'])->whereNumber('asset');
    Route::delete('{asset}', [MobileInventoryController::class, 'destroy'])->whereNumber('asset');
});

Route::prefix('hostels')->group(function (): void {
    Route::get('/', [MobileHostelController::class, 'index']);
    Route::post('/', [MobileHostelController::class, 'storeHostel']);
    Route::post('{hostel}/rooms', [MobileHostelController::class, 'storeRoom'])->whereNumber('hostel');
    Route::post('allocations', [MobileHostelController::class, 'allocate']);
    Route::post('allocations/{allocation}/vacate', [MobileHostelController::class, 'vacate'])->whereNumber('allocation');
});
