<?php

use App\Http\Controllers\Api\MobileCommunicationController;
use Illuminate\Support\Facades\Route;

// Loaded by bootstrap/app.php under /api/v1 with bearer-token authentication.
// Events are read-only notices for recipients; replies remain exclusive to
// message conversations.
Route::post('calendar/events', [MobileCommunicationController::class, 'storeEvent']);
