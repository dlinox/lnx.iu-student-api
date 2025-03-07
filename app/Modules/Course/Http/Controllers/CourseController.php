<?php

namespace App\Modules\Course\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Course\Http\Requests\CourseStoreRequest;
use App\Modules\Course\Http\Requests\CourseUpdateRequest;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Http\Resources\CourseDataTableItemsResource;
use App\Modules\Student\Models\Student;
use Illuminate\Support\Facades\Auth;
// use App\Modules\CurriculumModule\Models\CurriculumModule;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function getCurriculumCourses(Request $request)
    {
        try {
            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            $courses = Course::geCurriculumCourses($request->curriculumId, $request->moduleId, $student->id);
            return ApiResponse::success($courses);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
    //getExtracurricularCourses

    public function getExtracurricularCourses(Request $request)
    {
        try {

            $user = Auth::user();
            $courses = Course::getExtracurricularCourses($request->curriculumId, $user);
            return ApiResponse::success($courses);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
