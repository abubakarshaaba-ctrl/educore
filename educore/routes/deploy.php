<?php

use App\Http\Controllers\SelfWorkflowValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Shell-free native Android source/workflow validation. The deploy token is
// required inside the controller; throttling also limits repeated zipball/CI
// lookups on shared hosting. GitHub API credentials must come from server
// configuration (DEPLOY_GH_TOKEN), never from a URL/query string where they
// could be retained in access logs or browser history.
Route::get('/deploy/validate-android', function (Request $request, SelfWorkflowValidationController $controller) {
    abort_if($request->has('gh'), 400, 'GitHub credentials must be configured on the server, not supplied in the URL.');

    return $controller->android($request);
})
    ->middleware('throttle:5,10')
    ->name('deploy.validate-android');
