<?php

namespace App\Modules\DocumentType\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\DocumentType\Http\Requests\DocumentTypeStoreRequest;
use App\Modules\DocumentType\Http\Requests\SaveRequest;
use App\Modules\DocumentType\Models\DocumentType;
use App\Modules\DocumentType\Http\Resources\DocumentTypeDataTableItemsResource;

class DocumentTypeController extends Controller
{
    public function loadDataTable(Request $request)
    {
        try {
            $documentTypes = DocumentType::dataTable($request);
            DocumentTypeDataTableItemsResource::collection($documentTypes);
            return ApiResponse::success($documentTypes);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function save(SaveRequest $request)
    {
        try {
            $data = $request->validated();
            DocumentType::updateOrCreate(['id' => $request->id], $data);
            return ApiResponse::success(null, $request->id ? 'Registro actualizado correctamente' : 'Registro creado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $request->id ? 'Error al actualizar el registro' : 'Error al crear el registro');
        }
    }

    public function destroy(Request $request)
    {
        try {
            $documentType = DocumentType::find($request->id);
            $documentType->delete();
            return ApiResponse::success(null, 'Registro eliminado correctamente', 204);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function getItemsForSelect()
    {
        try {
            $documentTypes = DocumentType::select('id as value', 'name as label')->enabled()->get();
            return ApiResponse::success($documentTypes);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
