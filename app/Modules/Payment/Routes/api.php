<?php

use App\Modules\Payment\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/payments')->group(function () {
    Route::post('load-datatable', [PaymentController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [PaymentController::class, 'getFormItem']);
    Route::post('save-item', [PaymentController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [PaymentController::class, 'deleteItem']);
});
