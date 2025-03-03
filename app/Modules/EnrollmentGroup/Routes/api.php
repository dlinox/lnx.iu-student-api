<?php

use App\Modules\EnrollmentGroup\Http\Controllers\EnrollmentGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/enrollment_groups')->group(function () {
    Route::post('load-datatable', [EnrollmentGroupController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [EnrollmentGroupController::class, 'getFormItem']);
    Route::post('save-item', [EnrollmentGroupController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [EnrollmentGroupController::class, 'deleteItem']);
});
