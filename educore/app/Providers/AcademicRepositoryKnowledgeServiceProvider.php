<?php

namespace App\Providers;

use App\Http\Controllers\AcademicRepositoryIngestionController;
use App\Http\Controllers\AcademicRepositoryKnowledgeController;
use App\Http\Controllers\AcademicRepositoryLessonPlanController;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AcademicRepositoryKnowledgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'active.account', 'tenant', 'tenant.access', 'tenant.onboarding.complete', StaffOnly::class])
            ->prefix('academic-repository/knowledge')
            ->name('academic-repository.knowledge.')
            ->group(function (): void {
                Route::get('/', [AcademicRepositoryKnowledgeController::class, 'index'])->name('index');
                Route::get('ingestion', [AcademicRepositoryIngestionController::class, 'index'])->name('ingestion.index');
                Route::post('ingestion', [AcademicRepositoryIngestionController::class, 'store'])->name('ingestion.store');
                Route::get('create', [AcademicRepositoryKnowledgeController::class, 'create'])->name('create');
                Route::post('/', [AcademicRepositoryKnowledgeController::class, 'store'])->name('store');
                Route::get('{academicTopic}', [AcademicRepositoryKnowledgeController::class, 'show'])->name('show');
                Route::get('{academicTopic}/edit', [AcademicRepositoryKnowledgeController::class, 'edit'])->name('edit');
                Route::put('{academicTopic}', [AcademicRepositoryKnowledgeController::class, 'update'])->name('update');
                Route::post('{academicTopic}/approve', [AcademicRepositoryKnowledgeController::class, 'approve'])->name('approve');
                Route::post('{academicTopic}/save-to-lesson-planner', [AcademicRepositoryLessonPlanController::class, 'store'])->name('lesson-planner.store');
                Route::get('{academicTopic}/generate/{type}', [AcademicRepositoryKnowledgeController::class, 'generate'])
                    ->whereIn('type', ['lesson-plan', 'student-note'])
                    ->name('generate');
            });
    }
}
