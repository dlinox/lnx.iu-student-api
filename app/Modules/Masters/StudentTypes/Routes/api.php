<?php

use App\Modules\Masters\StudentTypes\Http\Controllers\StudentTypesController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/student-types')->group(function () {
    Route::get('/items/for-select', [StudentTypesController::class, 'getItemsForSelect']);
});
