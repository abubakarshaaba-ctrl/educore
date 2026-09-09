<?php

use App\Http\Controllers\Api\MobileClassController;
use Illuminate\Support\Facades\Route;

Route::get(
    'classes/{classArm}/students/{student}/results',
    [MobileClassController::class, 'results']
)->whereNumber('classArm')->whereNumber('student');
