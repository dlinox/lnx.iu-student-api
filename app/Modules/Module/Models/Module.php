<?php

namespace App\Modules\Module\Models;

use App\Traits\HasDataTable;
use App\Traits\HasEnabledState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Module extends Model
{
    use HasDataTable, HasEnabledState;

    protected $fillable = [
        'name',
        'description',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function getByCurriculum($curriculum_id)
    {
        $modules = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.description as description',
            'modules.is_enabled as is_enabled',
        )
            ->distinct()
            ->join('curriculum_courses', function ($join) use ($curriculum_id) {
                $join->on('modules.id', '=', 'curriculum_courses.module_id')
                    ->where('curriculum_courses.is_extracurricular', 0);
            })
            ->where('curriculum_courses.curriculum_id', $curriculum_id)
            ->get();
        return $modules;
    }

    public static function getModuleByCurriculum($curriculum_id, $id)
    {
        $module = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.description as description',
            'modules.is_enabled as isEnabled',

            DB::raw('count(curriculum_courses.id) as coursesCount'),
            DB::raw('sum(curriculum_courses.credits) as credits'),
            DB::raw('sum(curriculum_courses.hours_practice) as hoursPractice'),
            DB::raw('sum(curriculum_courses.hours_theory) as hoursTheory'),
            DB::raw('group_concat(distinct areas.name) as areas'),
            DB::raw('group_concat(distinct module_prices.price) as prices'),
            DB::raw('group_concat(distinct curriculum_courses.curriculum_id) as curriculumId'),
        )
            ->join('curriculum_courses', 'modules.id', '=', 'curriculum_courses.module_id')
            ->join('areas', 'curriculum_courses.area_id', '=', 'areas.id')
            ->leftJoin('module_prices', 'modules.id', '=', 'module_prices.module_id')
            ->where('curriculum_courses.curriculum_id', $curriculum_id)
            ->where('curriculum_courses.module_id', $id)
            ->groupby('modules.id')
            ->first();
        return $module;
    }
}
