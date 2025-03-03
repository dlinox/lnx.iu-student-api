<?php

namespace App\Modules\Student\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Student\Http\Resources\StudentDataTableItemResource;
use App\Modules\Student\Models\Student;
use App\Http\Responses\ApiResponse;
use App\Modules\Student\Http\Requests\StudentSaveRequest;
use App\Modules\Student\Http\Resources\StudentFormItemResource;

class StudentController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = Student::dataTable($request);
            StudentDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = Student::find($request->id);
            return ApiResponse::success(StudentFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(StudentSaveRequest $request)
    {
        try {
            $data = $request->validated();
            Student::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            Student::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
