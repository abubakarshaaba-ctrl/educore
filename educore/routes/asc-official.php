<?php

use App\Http\Controllers\AscDerivedController;
use App\Http\Controllers\AscExportController;
use App\Http\Controllers\AscReviewController;
use App\Http\Controllers\AscSectionController;
use Illuminate\Support\Facades\Route;

Route::get('asc/review', [AscReviewController::class, 'index'])
    ->name('asc.review');

Route::get('asc/export/pdf', [AscExportController::class, 'pdf'])
    ->name('asc.export.pdf');

Route::prefix('asc/section')->name('asc.section.')->group(function () {
    Route::get('{section}', [AscSectionController::class, 'show'])
        ->whereIn('section', ['b','d','f','g','h'])
        ->name('show');
    Route::post('{section}', [AscSectionController::class, 'save'])
        ->whereIn('section', ['b','d','f','g','h'])
        ->name('save');
});

Route::get('asc/derived/{section}', [AscDerivedController::class, 'show'])
    ->whereIn('section', ['c', 'e'])
    ->name('asc.derived.show');
