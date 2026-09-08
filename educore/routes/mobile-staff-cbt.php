<?php

use App\Http\Controllers\Api\StaffCbtCreateApiController;
use Illuminate\Support\Facades\Route;

// Loaded by bootstrap/app.php under /api/v1/staff/cbt with the same bearer
// token middleware used by the primary mobile API route file.
Route::get('options', [StaffCbtCreateApiController::class, 'options']);
Route::post('exams', [StaffCbtCreateApiController::class, 'store']);
