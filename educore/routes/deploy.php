<?php

use App\Http\Controllers\SelfGitCommitController;
use App\Http\Controllers\SelfSourceValidationController;
use Illuminate\Support\Facades\Route;

// Source-only preflight. It reads the branch and critical source contracts
// through repository APIs only and never queries or reruns GitHub Actions.
Route::get('/deploy/validate-source', [SelfSourceValidationController::class, 'android'])
    ->middleware('throttle:10,1')
    ->name('deploy.validate-source');

// Direct GitHub Git Database API writer. These endpoints do not execute git,
// shell commands, cPanel Git hooks or GitHub Actions. Authentication is sent in
// X-EduCore-Deploy-Token (or Authorization: Bearer <deploy token>); the GitHub
// write credential remains server-side as GITHUB_WRITE_TOKEN.
Route::get('/deploy/repository/status', [SelfGitCommitController::class, 'status'])
    ->middleware('throttle:10,1')
    ->name('deploy.repository.status');

Route::post('/deploy/repository/commit', [SelfGitCommitController::class, 'commit'])
    ->middleware('throttle:5,10')
    ->name('deploy.repository.commit');
