<?php

namespace App\Modules\EnrollmentDeadline\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\EnrollmentDeadline\Models\EnrollmentDeadline;

class EnrollmentDeadlineController extends Controller
{

    public function getActiveEnrollmentPeriod()
    {
        try {
            $period = EnrollmentDeadline::activeEnrollmentPeriod();
            // if (!$period) return ApiResponse::warning('No hay un periodo de matrícula activo', 'No hay un periodo de matrícula activo');
            return ApiResponse::success($period);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Ocurrió un error al obtener el periodo de matrícula activo');
        }
    }
}
