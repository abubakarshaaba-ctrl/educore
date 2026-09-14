<?php

namespace App\Providers;

use App\Http\Controllers\Api\MobileAcademicKnowledgeController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MobileAcademicKnowledgeApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/v1/academic-repository/knowledge')
            ->middleware(AuthenticateApiToken::class)
            ->group(function (): void {
                Route::get('/', [MobileAcademicKnowledgeController::class, 'index']);
                Route::get('{topic}', [MobileAcademicKnowledgeController::class, 'show'])->whereNumber('topic');
                Route::get('{topic}/generate/{type}', [MobileAcademicKnowledgeController::class, 'generate'])
                    ->whereNumber('topic')->where('type', 'lesson-plan|student-note');
                Route::post('{topic}/save-lesson-plan', [MobileAcademicKnowledgeController::class, 'saveLessonPlan'])->whereNumber('topic');
                Route::post('{topic}/save-student-note', [MobileAcademicKnowledgeController::class, 'saveStudentNote'])->whereNumber('topic');
            });
    }
}
