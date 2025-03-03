<?php

use App\Modules\Course\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/courses')->group(function () {
    Route::get('curriculum/{curriculumId}/module/{moduleId}', [CourseController::class, 'getCurriculumCourses']);
    //getExtracurricularCourses
    Route::get('extracurricular/{curriculumId}', [CourseController::class, 'getExtracurricularCourses']);
});
