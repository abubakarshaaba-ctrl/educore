<?php

use App\Http\Controllers\Api\PlatformNoticeController;
use Illuminate\Support\Facades\Route;

Route::get('platform-notices', [PlatformNoticeController::class, 'index']);
Route::post('platform-notices/{broadcast}/read', [PlatformNoticeController::class, 'markRead'])->whereNumber('broadcast');
Route::post('platform-notices/{broadcast}/dismiss', [PlatformNoticeController::class, 'dismiss'])->whereNumber('broadcast');
