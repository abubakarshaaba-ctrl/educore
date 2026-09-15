<?php

use App\Http\Controllers\AssessmentTemplateController;
use App\Http\Controllers\AssessmentTypeAdminController;
use Illuminate\Support\Facades\Route;

// Loaded after routes/web.php. The historical /scores/assessment-types URL is
// retained as the canonical navigation target, but its GET screen is now the
// smarter Assessment Template workflow. Legacy mutation routes remain available
// only for backwards compatibility with stale forms/bookmarks.
Route::prefix('scores')->name('scores.')->group(function (): void {
    Route::get('assessment-types', [AssessmentTemplateController::class, 'index'])
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

Route::prefix('assessment-templates')->name('assessment-templates.')->group(function (): void {
    Route::post('/', [AssessmentTemplateController::class, 'store'])->name('store');
    Route::put('{template}', [AssessmentTemplateController::class, 'update'])->name('update');
    Route::post('{template}/duplicate', [AssessmentTemplateController::class, 'duplicate'])->name('duplicate');
    Route::post('{template}/assign', [AssessmentTemplateController::class, 'assign'])->name('assign');
    Route::delete('{template}', [AssessmentTemplateController::class, 'destroy'])->name('destroy');
});
