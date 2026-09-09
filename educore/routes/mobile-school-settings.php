<?php

use App\Http\Controllers\Api\MobileSchoolSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('school-settings')->group(function (): void {
    Route::get('/', [MobileSchoolSettingsController::class, 'show']);
    Route::put('/', [MobileSchoolSettingsController::class, 'update']);
});
