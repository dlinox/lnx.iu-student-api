<?php

namespace App\Modules\EnrollmentDeadline\Models;

use App\Traits\HasDataTable;
use App\Traits\HasEnabledState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EnrollmentDeadline extends Model
{
    use HasDataTable, HasEnabledState;

    protected $fillable = [
        'start_date',
        'end_date',
        'type',
        'reference_id',
        'observations',
        'period_id',
        'virtual',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'virtual' => 'boolean',
    ];

    public static function activeEnrollmentPeriod()
    {
        $period = self::select(
            'enrollment_deadlines.period_id as periodId',
            'enrollment_deadlines.type',
            'enrollment_deadlines.start_date as startDate',
            'enrollment_deadlines.end_date as endDate',
            'enrollment_deadlines.virtual',
            DB::raw('CONCAT(periods.year, "-", view_month_constants.label) as period')
        )
            ->join('periods', 'enrollment_deadlines.period_id', '=', 'periods.id')
            ->join('view_month_constants', 'periods.month', '=', 'view_month_constants.value')
            ->where('enrollment_deadlines.start_date', '<=', now())
            ->where('enrollment_deadlines.end_date', '>=', now())
            ->where('enrollment_deadlines.virtual', true)
            ->first();

        return $period ? $period->toArray() : null;
    }
}
