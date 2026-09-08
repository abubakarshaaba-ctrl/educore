<?php

use App\Http\Controllers\Api\MobileSkillsController;
use Illuminate\Support\Facades\Route;

Route::prefix('skills')->group(function (): void {
    Route::get('/', [MobileSkillsController::class, 'index']);
    Route::get('sheet', [MobileSkillsController::class, 'sheet']);
    Route::put('sheet', [MobileSkillsController::class, 'save']);
});
