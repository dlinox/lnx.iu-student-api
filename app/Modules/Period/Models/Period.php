<?php

namespace App\Modules\Period\Models;

use App\Traits\HasEnabledState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Period extends Model
{
    use  HasEnabledState;

    protected $fillable = [
        'year',
        'month',
        'enrollment_enabled',
        'is_enabled',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'enrollment_enabled' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public static function enrollmentPeriod()
    {
        $period = self::select(
            'periods.id as id',
            DB::raw('CONCAT(year, "-", UPPER(months.name)) as name'),
        )->join('months', 'periods.month', '=', 'months.id')
            ->where('status', 'MATRICULA')
            ->first();

        return $period ? $period : null;
    }
}
