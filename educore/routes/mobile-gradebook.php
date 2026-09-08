<?php

use App\Http\Controllers\Api\MobileGradebookController;
use Illuminate\Support\Facades\Route;

Route::prefix('gradebook')->group(function (): void {
    Route::get('/', [MobileGradebookController::class, 'index']);
    Route::put('remarks/{summary}', [MobileGradebookController::class, 'updateRemark'])
        ->whereNumber('summary');
});
