<?php

namespace App\Modules\Schedule\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Schedule\Http\Resources\ScheduleDataTableItemResource;
use App\Modules\Schedule\Models\Schedule;
use App\Http\Responses\ApiResponse;
use App\Modules\Schedule\Http\Requests\ScheduleSaveRequest;
use App\Modules\Schedule\Http\Resources\ScheduleFormItemResource;

class ScheduleController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = Schedule::dataTable($request);
            ScheduleDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = Schedule::find($request->id);
            return ApiResponse::success(ScheduleFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(ScheduleSaveRequest $request)
    {
        try {
            $data = $request->validated();
            Schedule::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            Schedule::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
