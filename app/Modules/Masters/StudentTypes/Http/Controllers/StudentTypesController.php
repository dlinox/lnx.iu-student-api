<?php

namespace App\Modules\Masters\StudentTypes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Masters\StudentTypes\Models\StudentType;
use Illuminate\Support\Facades\Cache;

class StudentTypesController extends Controller
{
   
    public function getItemsForSelect()
    {
        try {
            $items = Cache::remember('students_types_for_select', now()->addMinutes(60), function () {
                return StudentType::getItemsForSelect();
            });

            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }
}
