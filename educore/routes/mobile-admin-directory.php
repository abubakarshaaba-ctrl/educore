<?php

use App\Http\Controllers\Api\MobileStaffDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('admin/staff', MobileStaffDirectoryController::class);
Route::patch('admin/staff/{member}', [MobileStaffDirectoryController::class, 'update'])
    ->whereNumber('member');
