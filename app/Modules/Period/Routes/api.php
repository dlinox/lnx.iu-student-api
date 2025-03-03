<?php

use App\Modules\Period\Http\Controllers\PeriodController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/period')->group(function () {
    Route::get('current', [PeriodController::class, 'getCurrent']);
    Route::get('enrollment-period', [PeriodController::class, 'getEnrollmentPeriod']);
});
