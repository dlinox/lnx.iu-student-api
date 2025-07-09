<?php

namespace App\Modules\Module\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Module\Models\Module;
use App\Models\Student;
use App\Modules\EnrollmentDeadline\Models\EnrollmentDeadline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{

    public function getByCurriculum(Request $request)
    {
        try {
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            $curriculums = DB::table('curriculums')->select('curriculums.id')
                ->where('curriculums.is_enabled', true)
                ->get()->pluck('id')->toArray();

            $modules = Module::getByCurriculum($curriculums, $student->id, $request->onlyEnrolled ? true : false);
            return ApiResponse::success($modules);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function getModuleByCurriculum(Request $request)
    {
        try {

            $user = Auth::user();

            $student = Student::select('students.id', 'students.student_type_id')
                ->where('students.id', $user->model_id)
                ->first();

            $module = Module::getModuleByCurriculum($request->id, $student->student_type_id);

            $enrollment = DB::table('enrollment_groups')
                ->join('groups', 'enrollment_groups.group_id', '=', 'groups.id')
                ->join('courses', 'groups.course_id', '=', 'courses.id')
                ->join('modules', 'courses.module_id', '=', 'modules.id')
                ->where('enrollment_groups.student_id', $student->id)
                ->where('enrollment_groups.status', 'MATRICULADO')
                ->where('modules.id', $request->id)
                ->exists();
            $module->isEnrolled = $enrollment ? true : false;

            return ApiResponse::success($module);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
    //getEnabledByPeriod
    public function getModulesEnabledOnPeriod()
    {
        try {
            $period = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$period) return ApiResponse::success([]);
            $modules = Module::getEnabledOnPeriod($period['periodId']);
            return ApiResponse::success($modules);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
