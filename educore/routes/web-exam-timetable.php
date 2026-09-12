<?php

use App\Http\Controllers\ExamTimetableController;
use Illuminate\Support\Facades\Route;

Route::prefix('exam-timetable')->name('exam-timetable.')->group(function () {
    Route::get('/', [ExamTimetableController::class, 'index'])->name('index');
    Route::get('my-supervision', [ExamTimetableController::class, 'mySupervision'])->name('my-supervision');
    Route::post('/', [ExamTimetableController::class, 'store'])->name('store');
    Route::patch('{examSchedule}', [ExamTimetableController::class, 'update'])->name('update');
    Route::delete('{examSchedule}', [ExamTimetableController::class, 'destroy'])->name('destroy');
    Route::post('{examSchedule}/supervisors', [ExamTimetableController::class, 'assignSupervisor'])->name('supervisors.store');
    Route::delete('{examSchedule}/supervisors/{supervision}', [ExamTimetableController::class, 'removeSupervisor'])->name('supervisors.destroy');
});
