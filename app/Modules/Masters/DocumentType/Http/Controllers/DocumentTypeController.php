<?php

namespace App\Modules\Masters\DocumentType\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;

use App\Modules\Masters\DocumentType\Models\DocumentType;
use Illuminate\Support\Facades\Cache;

class DocumentTypeController extends Controller
{

    public function getItemsForSelect()
    {
        try {
            $items = Cache::remember('document_types_for_select', now()->addMinutes(60), function () {
                return DocumentType::getItemsForSelect();
            });

            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }
}
