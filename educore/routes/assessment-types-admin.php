<?php

use App\Http\Controllers\AssessmentTypeAdminController;
use Illuminate\Support\Facades\Route;

// Loaded after routes/web.php so these definitions replace the legacy
// assessment-type handlers without disturbing the rest of the score module.
Route::prefix('scores')->name('scores.')->group(function (): void {
    Route::get('assessment-types', [AssessmentTypeAdminController::class, 'index'])
        ->name('assessment-types');
    Route::post('assessment-types', [AssessmentTypeAdminController::class, 'store'])
        ->name('assessment-types.store');
    Route::put('assessment-types/{at}', [AssessmentTypeAdminController::class, 'update'])
        ->name('assessment-types.update');
    Route::patch('assessment-types/{at}/migrate', [AssessmentTypeAdminController::class, 'migrate'])
        ->name('assessment-types.migrate');
    Route::delete('assessment-types/{at}', [AssessmentTypeAdminController::class, 'destroy'])
        ->name('assessment-types.destroy');

    Route::post('assessment-schemes/templates', [AssessmentTypeAdminController::class, 'storeTemplate'])
        ->name('assessment-schemes.templates.store');
    Route::post('assessment-schemes/templates/{template}/apply', [AssessmentTypeAdminController::class, 'applyTemplate'])
        ->name('assessment-schemes.templates.apply');
    Route::delete('assessment-schemes/templates/{template}', [AssessmentTypeAdminController::class, 'destroyTemplate'])
        ->name('assessment-schemes.templates.destroy');
});
