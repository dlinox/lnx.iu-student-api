<?php

namespace App\Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Schedule\Models\Schedule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{

    public function getAvailableSchedules()
    {
        $user = Auth::user();
        $shedule = Schedule::byStudent($user->model_id);
        return ApiResponse::success($shedule);
    }
}
