<?php

namespace App\Modules\Person\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Person\Http\Resources\PersonDataTableItemResource;
use App\Modules\Person\Models\Person;
use App\Http\Responses\ApiResponse;
use App\Modules\Person\Http\Requests\PersonSaveRequest;
use App\Modules\Person\Http\Resources\PersonFormItemResource;

class PersonController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = Person::dataTable($request);
            PersonDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = Person::find($request->id);
            return ApiResponse::success(PersonFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(PersonSaveRequest $request)
    {
        try {
            $data = $request->validated();
            Person::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            Person::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
