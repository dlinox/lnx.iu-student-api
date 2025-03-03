<?php

use App\Modules\Schedule\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/schedules')->group(function () {
    Route::post('load-datatable', [ScheduleController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [ScheduleController::class, 'getFormItem']);
    Route::post('save-item', [ScheduleController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [ScheduleController::class, 'deleteItem']);
});
