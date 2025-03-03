<?php

use App\Modules\Module\Http\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/modules')->group(function () {
    Route::get('curriculum/{id}', [ModuleController::class, 'getByCurriculum']);
    Route::get('{id}/curriculum/{curriculumId}', [ModuleController::class, 'getModuleByCurriculum']);
});
