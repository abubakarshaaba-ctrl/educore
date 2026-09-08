<?php

use App\Http\Controllers\SelfWorkflowValidationController;
use Illuminate\Support\Facades\Route;

// Shell-free native Android source/workflow validation. The deploy token is
// required inside the controller; throttling also limits repeated zipball/CI
// lookups on shared hosting.
Route::get('/deploy/validate-android', [SelfWorkflowValidationController::class, 'android'])
    ->middleware('throttle:5,10')
    ->name('deploy.validate-android');
