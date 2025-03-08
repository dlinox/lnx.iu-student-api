<?php

namespace App\Modules\Module\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Module\Models\Module;
use App\Modules\Student\Models\Student;
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

            $enrollment = Enrollment::where('module_id', $request->id)
                ->where('student_id', $student->id)
                ->first();

            $module->isEnrolled = $enrollment ? true : false;

            return ApiResponse::success($module);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
