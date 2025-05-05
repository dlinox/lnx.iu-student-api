<?php

use App\Modules\Schedule\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/schedules')->middleware('auth:sanctum')->group(function () {
    Route::get('/available', [ScheduleController::class, 'getAvailableSchedules']);
});
