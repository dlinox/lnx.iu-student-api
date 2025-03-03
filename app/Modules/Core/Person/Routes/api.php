<?php

use App\Modules\Person\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/people')->group(function () {
    Route::post('load-datatable', [PersonController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [PersonController::class, 'getFormItem']);
    Route::post('save-item', [PersonController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [PersonController::class, 'deleteItem']);
});
