<?php

namespace App\Providers;

use App\Http\Controllers\CbtScoreSheetLinkController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CbtScoreSheetLinkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'active.account', 'tenant'])
            ->put('/cbt/exams/{exam}/score-sheet-link', [CbtScoreSheetLinkController::class, 'update'])
            ->name('cbt.exams.score-sheet-link');
    }
}
