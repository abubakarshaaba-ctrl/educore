<?php

use App\Http\Controllers\Api\MobileClassController;
use App\Http\Controllers\Api\MobileExpensesController;
use App\Http\Controllers\Api\MobileFeesController;
use App\Http\Controllers\Api\MobilePayrollController;
use Illuminate\Support\Facades\Route;

Route::get('classes/{classArm}/students/{student}/results', [MobileClassController::class, 'results'])
    ->whereNumber('classArm')
    ->whereNumber('student');

Route::prefix('fees')->group(function (): void {
    Route::get('/', [MobileFeesController::class, 'index']);
    Route::post('generate', [MobileFeesController::class, 'generate']);
    Route::post('invoices/{invoice}/payments', [MobileFeesController::class, 'recordPayment'])->whereNumber('invoice');
});

Route::prefix('expenses')->group(function (): void {
    Route::get('/', [MobileExpensesController::class, 'index']);
    Route::post('/', [MobileExpensesController::class, 'store']);
    Route::patch('{expense}', [MobileExpensesController::class, 'update'])->whereNumber('expense');
    Route::delete('{expense}', [MobileExpensesController::class, 'destroy'])->whereNumber('expense');
});

Route::prefix('payroll')->group(function (): void {
    Route::get('/', [MobilePayrollController::class, 'index']);
    Route::post('/', [MobilePayrollController::class, 'generate']);
    Route::get('{period}', [MobilePayrollController::class, 'show'])->whereNumber('period');
    Route::post('{period}/approve', [MobilePayrollController::class, 'approve'])->whereNumber('period');
    Route::post('{period}/paid', [MobilePayrollController::class, 'markPaid'])->whereNumber('period');
});
