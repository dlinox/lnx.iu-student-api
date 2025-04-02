<?php

use App\Modules\Module\Http\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/modules')->middleware('auth:sanctum')->group(function () {
    Route::post('curriculum', [ModuleController::class, 'getByCurriculum']);
    Route::get('{id}/curriculum/{curriculumId}', [ModuleController::class, 'getModuleByCurriculum']);
    // getModulesEnabledByPeriod
    Route::get('enabled-on-period', [ModuleController::class, 'getModulesEnabledOnPeriod']);
});
