<?php

namespace App\Modules\Group\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Group\Http\Resources\GroupDataTableItemResource;
use App\Modules\Group\Models\Group;
use App\Http\Responses\ApiResponse;
use App\Modules\Group\Http\Requests\GroupSaveRequest;
use App\Modules\Group\Http\Resources\GroupFormItemResource;

class GroupController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = Group::dataTable($request);
            GroupDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = Group::find($request->id);
            return ApiResponse::success(GroupFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(GroupSaveRequest $request)
    {
        try {
            $data = $request->validated();
            Group::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            Group::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
