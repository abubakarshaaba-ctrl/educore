<?php

use App\Http\Controllers\SelfGitCommitController;
use App\Http\Controllers\SelfWorkflowValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Shell-free native Android source/workflow validation. The deploy token is
// required inside the controller; throttling also limits repeated zipball/CI
// lookups on shared hosting. GitHub API credentials must come from server
// configuration, never from a URL/query string where they could be retained in
// access logs or browser history.
Route::get('/deploy/validate-android', function (Request $request, SelfWorkflowValidationController $controller) {
    abort_if($request->has('gh'), 400, 'GitHub credentials must be configured on the server, not supplied in the URL.');

    return $controller->android($request);
})
    ->middleware('throttle:5,10')
    ->name('deploy.validate-android');

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
