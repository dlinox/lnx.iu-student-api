<?php

use App\Modules\Enrollment\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/enrollment')->middleware('auth:sanctum')->group(function () {
    Route::post('store-student-enrollment', [EnrollmentController::class, 'storeStudentEnrollment']);
    //storeGroupEnrollment
    Route::post('store-group-enrollment', [EnrollmentController::class, 'storeGroupEnrollment']);
    //enabledGroupsEnrollment
    Route::post('enabled-groups-enrollment', [EnrollmentController::class, 'enabledGroupsEnrollment']);
});
