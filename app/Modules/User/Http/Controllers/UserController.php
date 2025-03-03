<?php

namespace App\Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\User\Http\Resources\UserDataTableItemResource;
use App\Modules\User\Models\User;
use App\Http\Responses\ApiResponse;
use App\Modules\User\Http\Requests\UserSaveRequest;
use App\Modules\User\Http\Resources\UserFormItemResource;

class UserController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = User::dataTable($request);
            UserDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = User::find($request->id);
            return ApiResponse::success(UserFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(UserSaveRequest $request)
    {
        try {
            $data = $request->validated();
            User::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            User::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
