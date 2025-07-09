<?php

namespace App\Modules\Course\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Course\Models\Course;
use App\Models\Student;
use App\Modules\EnrollmentDeadline\Models\EnrollmentDeadline;
use App\Modules\Period\Models\Period;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function getCurriculumCourses(Request $request)
    {
        try {
            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);
            $courses = Course::getCurriculumCourses($request->moduleId, $student->id);
            return ApiResponse::success($courses);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }


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

    public function getCoursesEnabled(Request $request)
    {
        try {
            $period = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$period) return ApiResponse::success([]);
            $courses = Course::getCoursesEnabled($period['periodId'], $request->limit);
            return ApiResponse::success($courses);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //getByModuleForSelect
    public function getByModuleForSelect(Request $request)
    {
        try {
            $courses = Course::getByModuleForSelect($request->moduleId);
            return ApiResponse::success($courses);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function getEnabledGroups()
    {
        try {
            $period = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$period) return ApiResponse::success([]);
            $groups = Course::getEnabledGroups($period['periodId']);
            return ApiResponse::success($groups);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
