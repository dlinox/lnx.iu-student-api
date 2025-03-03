<?php

namespace App\Modules\EnrollmentGroup\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\EnrollmentGroup\Http\Resources\EnrollmentGroupDataTableItemResource;
use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
use App\Http\Responses\ApiResponse;
use App\Modules\EnrollmentGroup\Http\Requests\EnrollmentGroupSaveRequest;
use App\Modules\EnrollmentGroup\Http\Resources\EnrollmentGroupFormItemResource;

class EnrollmentGroupController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = EnrollmentGroup::dataTable($request);
            EnrollmentGroupDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = EnrollmentGroup::find($request->id);
            return ApiResponse::success(EnrollmentGroupFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(EnrollmentGroupSaveRequest $request)
    {
        try {
            $data = $request->validated();
            EnrollmentGroup::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            EnrollmentGroup::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
