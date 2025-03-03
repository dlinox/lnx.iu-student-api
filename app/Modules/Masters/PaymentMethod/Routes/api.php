<?php

use App\Modules\PaymentMethod\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/payment_methods')->group(function () {
    Route::post('load-datatable', [PaymentMethodController::class, 'loadDataTable']);
    Route::get('get-form-item/{id}', [PaymentMethodController::class, 'getFormItem']);
    Route::post('save-item', [PaymentMethodController::class, 'saveItem']);
    Route::delete('delete-item/{id}', [PaymentMethodController::class, 'deleteItem']);
});
