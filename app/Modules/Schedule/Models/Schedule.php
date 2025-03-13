<?php

namespace App\Modules\Schedule\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Model;

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
}
