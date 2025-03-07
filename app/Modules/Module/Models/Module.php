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
        'code',
        'description',
        'curriculum_id',
        'is_enabled',
        'is_extracurricular',
    ];

    protected $casts = [
        'curriculum_id' => 'integer',
        'is_extracurricular' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    static $searchColumns = [
        'modules.name',
        'modules.code',
        'curriculums.name',
    ];

    public static function getByCurriculum($curriculumId)
    {
        $modules = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.description as description',
            'modules.is_enabled as is_enabled',
            DB::raw('count(courses.id) as coursesCount'),
            DB::raw('sum(courses.credits) as credits'),
            DB::raw('sum(courses.hours_practice) as hoursPractice'),
            DB::raw('sum(courses.hours_theory) as hoursTheory'),
            DB::raw('min(course_prices.presential_price) as minPresentialPrice'),
            DB::raw('min(course_prices.virtual_price) as minVirtualPrice'),
            //minimo de ambas modalidades
        )
            ->distinct()
            ->join('courses', 'modules.id', '=', 'courses.module_id')
            ->leftJoin('course_prices', 'courses.id', '=', 'course_prices.course_id')
            ->where('modules.curriculum_id', $curriculumId)
            ->groupby('modules.id')
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

            DB::raw('count(courses.id) as coursesCount'),
            DB::raw('sum(courses.credits) as credits'),
            DB::raw('sum(courses.hours_practice) as hoursPractice'),
            DB::raw('sum(courses.hours_theory) as hoursTheory'),
            DB::raw('group_concat(distinct areas.name) as areas'),
            DB::raw('group_concat(distinct module_prices.price) as prices'),
            DB::raw('group_concat(distinct courses.curriculum_id) as curriculumId'),
        )
            ->join('courses', 'modules.id', '=', 'courses.module_id')
            ->join('areas', 'courses.area_id', '=', 'areas.id')
            ->leftJoin('module_prices', 'modules.id', '=', 'module_prices.module_id')
            ->where('courses.curriculum_id', $curriculum_id)
            ->where('courses.module_id', $id)
            ->groupby('modules.id')
            ->first();
        return $module;
    }
}
