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

// AppServiceProvider owns the canonical template CRUD/assignment routes.
// Only the component-level routes are registered here to avoid duplicate route
// names when Laravel caches routes in production.
Route::prefix('assessment-templates')->name('assessment-templates.')->group(function (): void {
    Route::post('{template}/components', [AssessmentTemplateController::class, 'storeComponent'])
        ->name('components.store');
    Route::put('{template}/components/{component}', [AssessmentTemplateController::class, 'updateComponent'])
        ->name('components.update');
    Route::delete('{template}/components/{component}', [AssessmentTemplateController::class, 'destroyComponent'])
        ->name('components.destroy');
});
