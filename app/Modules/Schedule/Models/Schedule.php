<?php

namespace App\Modules\Schedule\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Schedule extends Model
{
    use HasDataTable;

    protected $fillable = ['is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $hidden = ['created_at', 'updated_at'];

    static $searchColumns = [];

    public static function byGroup($groupId)
    {
        $shedule =  self::select(
            'start_hour as startHour',
            'end_hour as endHour',
        )
            ->selectRaw('GROUP_CONCAT(`day`) AS days')
            ->where('group_id', $groupId)
            ->groupBy('start_hour', 'end_hour')
            ->first();

        if (!$shedule) {
            return null;
        }

        $shedule->startHour = date('h:i A', strtotime($shedule->startHour));
        $shedule->endHour = date('h:i A', strtotime($shedule->endHour));

        return $shedule;
    }

    public static function byStudent($studentId)
    {

        $shedule = self::select(
            'day',
            'days.name AS dayName',
            DB::raw('GROUP_CONCAT(CONCAT(start_hour, "-", end_hour) ORDER BY start_hour) AS hours')
        )
            ->join('groups', 'groups.id', '=', 'schedules.group_id')
            ->join('enrollment_groups', 'enrollment_groups.group_id', '=', 'schedules.group_id')
            ->join('days', 'days.short_name', '=', 'schedules.day')
            ->where('enrollment_groups.student_id', $studentId)
            ->whereIn('groups.status', ['ABIERTO', 'CERRADO'])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        if ($shedule->isEmpty()) {
            return [];
        }

        $shedule->map(function ($day) {
            $day->hours = explode(',', $day->hours);

            $day->hours = array_map(function ($hour) {
                $hour = explode('-', $hour);
                $hour[0] = date('h:i A', strtotime($hour[0]));
                $hour[1] = date('h:i A', strtotime($hour[1]));
                
                $hour = $hour[0] . ' a ' . $hour[1];
                return $hour;
            }, $day->hours);
            return $day;
        });

        return $shedule;
    }
}
