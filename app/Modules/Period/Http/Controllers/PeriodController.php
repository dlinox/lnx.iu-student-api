<?php

namespace App\Modules\Period\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Period\Models\Period;

class PeriodController extends Controller
{

    public function getCurrent()
    {
        try {
            $item = Period::current();
            if ($item == null) {
                return ApiResponse::warning(null, 'No se encontró un periodo activo');
            }
            return ApiResponse::success($item);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al obtener el periodo actual');
        }
    }

    public function getEnrollmentPeriod()
    {
        try {
            $item = Period::enrollmentPeriod();
            if ($item == null) {
                return ApiResponse::warning(null, 'No se encontró un periodo de matrícula activo');
            }
            return ApiResponse::success($item);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al obtener el periodo de matrícula');
        }
    }
}
