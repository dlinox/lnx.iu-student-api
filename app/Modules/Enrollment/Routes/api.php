<?php

use App\Modules\Enrollment\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/enrollment')->middleware('auth:sanctum')->group(function () {
    Route::post('load-data', [EnrollmentController::class, 'loadDataTable']);
    Route::post('store-student-enrollment', [EnrollmentController::class, 'storeStudentEnrollment']);
    //storeGroupEnrollment
    Route::post('store-group-enrollment', [EnrollmentController::class, 'storeGroupEnrollment']);
    //updateGroupEnrollment
    Route::post('update-group-enrollment', [EnrollmentController::class, 'updateGroupEnrollment']);
    //reserverGroupEnrollment
    Route::post('reserver-group-enrollment', [EnrollmentController::class, 'reserverGroupEnrollment']);
    //cancelGroupEnrollment
    Route::post('cancel-group-enrollment', [EnrollmentController::class, 'cancelGroupEnrollment']);
    //enabledGroupsEnrollment
    Route::post('enabled-groups-enrollment', [EnrollmentController::class, 'enabledGroupsEnrollment']);
});
