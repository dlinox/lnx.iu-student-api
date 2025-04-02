<?php

use Illuminate\Support\Facades\Route;
use App\Modules\EnrollmentDeadline\Http\Controllers\EnrollmentDeadlineController;

Route::prefix('api/enrollment-deadlines')->middleware('auth:sanctum')->group(function () {
    Route::get('active-enrollment-period', [EnrollmentDeadlineController::class, 'getActiveEnrollmentPeriod']);
});
