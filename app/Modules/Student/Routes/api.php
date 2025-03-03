<?php

use App\Modules\Student\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/students')->group(function () {
    Route::post('load-datatable', [StudentController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [StudentController::class, 'getFormItem']);
    Route::post('save-item', [StudentController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [StudentController::class, 'deleteItem']);
});
