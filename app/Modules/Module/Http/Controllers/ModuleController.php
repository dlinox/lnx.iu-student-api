<?php

namespace App\Modules\Module\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\Module\Models\Module;
use App\Modules\Student\Models\Student;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{

    public function getByCurriculum(Request $request)
    {
        try {
            $modules = Module::getByCurriculum($request->id);
            return ApiResponse::success($modules);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function getModuleByCurriculum(Request $request)
    {
        try {
            $module = Module::getModuleByCurriculum($request->curriculumId, $request->id);

            $user = Auth::user();

            $student = Student::select('students.id')
                ->where('students.id', $user->model_id)
                ->first();

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
