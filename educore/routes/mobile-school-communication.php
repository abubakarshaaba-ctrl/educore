<?php

use App\Http\Controllers\Api\MobileCommunicationController;
use App\Http\Controllers\Api\SchoolCommunicationApiController;
use Illuminate\Support\Facades\Route;

Route::get('school-messages', [SchoolCommunicationApiController::class, 'index']);
Route::get('school-messages/recipients', [SchoolCommunicationApiController::class, 'recipients']);
Route::post('school-messages', [SchoolCommunicationApiController::class, 'store']);
Route::get('school-messages/{thread}', [SchoolCommunicationApiController::class, 'show'])->whereNumber('thread');
Route::post('school-messages/{thread}/reply', [SchoolCommunicationApiController::class, 'reply'])->whereNumber('thread');

Route::post('calendar/events', [MobileCommunicationController::class, 'storeEvent']);
