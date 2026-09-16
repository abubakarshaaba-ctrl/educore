<?php

use App\Http\Controllers\AscSectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('asc/section')->name('asc.section.')->group(function () {
    Route::get('{section}', [AscSectionController::class, 'show'])
        ->whereIn('section', ['b','d','f','g','h'])
        ->name('show');
    Route::post('{section}', [AscSectionController::class, 'save'])
        ->whereIn('section', ['b','d','f','g','h'])
        ->name('save');
});
