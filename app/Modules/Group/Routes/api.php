<?php

use App\Modules\Group\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/groups')->group(function () {
    Route::post('load-datatable', [GroupController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [GroupController::class, 'getFormItem']);
    Route::post('save-item', [GroupController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [GroupController::class, 'deleteItem']);
});
