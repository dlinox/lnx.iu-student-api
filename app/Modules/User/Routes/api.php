s<?php

use App\Modules\User\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/users')->group(function () {
    Route::post('load-datatable', [UserController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [UserController::class, 'getFormItem']);
    Route::post('save-item', [UserController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [UserController::class, 'deleteItem']);
});
