<?php

namespace App\Modules\PaymentMethod\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\PaymentMethod\Http\Resources\PaymentMethodDataTableItemResource;
use App\Modules\PaymentMethod\Models\PaymentMethod;
use App\Http\Responses\ApiResponse;
use App\Modules\PaymentMethod\Http\Requests\PaymentMethodSaveRequest;
use App\Modules\PaymentMethod\Http\Resources\PaymentMethodFormItemResource;

class PaymentMethodController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = PaymentMethod::dataTable($request);
            PaymentMethodDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = PaymentMethod::find($request->id);
            return ApiResponse::success(PaymentMethodFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(PaymentMethodSaveRequest $request)
    {
        try {
            $data = $request->validated();
            PaymentMethod::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            PaymentMethod::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
