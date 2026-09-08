<?php

use App\Http\Controllers\PublicMarketingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// NOTE: this file is intentionally preserved as the application web-route
// entry point. Route groups/modules below remain in their existing order.

// ── Authentication ───────────────────────────────────────────────────────
Route::get('/', [PublicMarketingController::class, 'index'])->name('home');
Route::get('/privacy', [PublicMarketingController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [PublicMarketingController::class, 'terms'])->name('legal.terms');
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

// Shell-free deployment (cPanel Git deploy requires shell access this host
// lacks). Pulls master from GitHub and syncs the deployable paths.
// Trigger: /deploy/pull?token=<DEPLOY_TOKEN>
Route::get('/deploy/pull', [\App\Http\Controllers\SelfDeployController::class, 'pull'])
    ->middleware('throttle:5,10')
    ->name('deploy.pull');

// Shell-free Android source/workflow validation. This is the independent
// fallback used when GitHub Actions fails before allocating a runner.
// Trigger: /deploy/validate-android?token=<DEPLOY_TOKEN>&ref=mobile-overhaul
Route::get('/deploy/validate-android', [\App\Http\Controllers\SelfWorkflowValidationController::class, 'android'])
    ->middleware('throttle:3,10')
    ->name('deploy.validate-android');

// Unified mobile app download — serves the signed production APK.
Route::get('/download/app', function () {
    // Prefer the canonical product filename; retain compatibility with older
    // uploaded filenames while installations transition to the unified app.
    $preferred = public_path('downloads/EduCore.apk');
    $apk = file_exists($preferred) ? $preferred : null;

    if (!$apk) {
        $candidates = glob(public_path('downloads/*.apk')) ?: [];
        if ($candidates) {
            usort($candidates, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $apk = $candidates[0];
        }
    }

    if (!$apk) {
        return response()->view('app-download-soon', [], 404);
    }

    return response()->download($apk, 'EduCore.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('app.download');

// Quick diagnostic: what .apk files exist in the downloads folder.
Route::get('/download/app/_debug', function (Illuminate\Http\Request $r) {
    abort_unless($r->query('token') === (config('app.deploy_token') ?: \App\Http\Controllers\SelfDeployController::derivedToken()), 403);
    $files = [];
    foreach (glob(public_path('downloads/*')) ?: [] as $f) {
        $files[] = ['name' => basename($f), 'size' => filesize($f)];
    }
    return response()->json(['dir' => public_path('downloads'), 'files' => $files]);
});
Route::post('/contact', [PublicMarketingController::class, 'sendContact'])->middleware('throttle:public-form')->name('contact.submit');
Route::post('/school-onboarding', [PublicMarketingController::class, 'sendSchoolOnboarding'])->middleware('throttle:public-form')->name('school-onboarding.submit');
Route::get('/get-started',  [\App\Http\Controllers\SchoolRegistrationController::class, 'show'])->name('school.register');
