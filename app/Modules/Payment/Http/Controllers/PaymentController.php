<?php

namespace App\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Payment\Http\Resources\PaymentDataTableItemResource;
use App\Modules\Payment\Models\Payment;
use App\Http\Responses\ApiResponse;
use App\Modules\Payment\Http\Requests\PaymentSaveRequest;
use App\Modules\Payment\Http\Resources\PaymentFormItemResource;

class PaymentController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $items = Payment::dataTable($request);
            PaymentDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function getFormItem(Request $request)
    {
        try {
            $item = Payment::find($request->id);
            return ApiResponse::success(PaymentFormItemResource::make($item));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar el registro');
        }
    }

    public function saveItem(PaymentSaveRequest $request)
    {
        try {
            $data = $request->validated();
            Payment::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, 'Registro guardado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al guardar el registro');
        }
    }

    public function deleteItem(Request $request)
    {
        try {
            Payment::destroy($request->id);
            return ApiResponse::success(null, 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al eliminar el registro');
        }
    }
}
