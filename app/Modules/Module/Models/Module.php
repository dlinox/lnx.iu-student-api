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

    public static function getByCurriculum($curriculums, $studentId, $onlyEnrolled = false)
    {
        $modules = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.description as description',
            'modules.is_enabled as isEnabled',
            'modules.is_extracurricular as isExtracurricular',
            DB::raw('count(DISTINCT courses.id) as coursesCount'),
            DB::raw('sum(courses.credits) as credits'),
            DB::raw('sum(courses.hours_practice) as hoursPractice'),
            DB::raw('sum(courses.hours_theory) as hoursTheory'),
            DB::raw('min(course_prices.presential_price) as minPresentialPrice'),
            DB::raw('min(course_prices.virtual_price) as minVirtualPrice'),
            DB::raw('CASE WHEN COUNT(DISTINCT enrollments.id) > 0 THEN true ELSE false END as hasEnrolled'),
        )
            ->distinct()
            ->join('courses', 'modules.id', '=', 'courses.module_id')
            ->leftJoin('enrollments', function ($join) use ($studentId) {
                $join->on('modules.id', '=', 'enrollments.module_id')
                    ->where('enrollments.student_id', $studentId);
            })
            ->leftJoin('course_prices', 'courses.id', '=', 'course_prices.course_id')
            ->whereIn('courses.curriculum_id', $curriculums)
            ->when($onlyEnrolled === true, function ($query) use ($studentId) {
                $query->whereRaw('modules.id in (select DISTINCT module_id from enrollments where student_id = ?)', [$studentId]);
            })
            ->where('courses.is_enabled', true)
            ->groupby('modules.id')
            ->orderBy('modules.name')
            ->get()->map(function ($module) {
                $module->hasEnrolled = $module->hasEnrolled  == 1 ? true : false;
                return $module;
            });
        return $modules;
    }


    public static function getModuleByCurriculum($id, $studentTypeId)
    {
        $module = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.description as description',
            'modules.is_enabled as isEnabled',

            DB::raw('count(Distinct courses.id) as coursesCount'),
            DB::raw('sum(courses.credits) as credits'),
            DB::raw('sum(courses.hours_practice) as hoursPractice'),
            DB::raw('sum(courses.hours_theory) as hoursTheory'),
            DB::raw('group_concat(distinct areas.name) as areas'),
            DB::raw('group_concat(distinct module_prices.price) as prices'),
            DB::raw('group_concat(distinct courses.curriculum_id) as curriculumId'),
        )
            ->join('courses', 'modules.id', '=', 'courses.module_id')
            ->join('areas', 'courses.area_id', '=', 'areas.id')
            ->leftJoin('module_prices', function ($join) use ($studentTypeId) {
                $join->on('modules.id', '=', 'module_prices.module_id')
                    ->where('module_prices.student_type_id', $studentTypeId);
            })
            ->where('courses.module_id', $id)
            ->groupby('modules.id')
            ->first();
        return $module;
    }

    public static function getEnabledOnPeriod($periodId)
    {
        $modules = self::select(
            'modules.id as id',
            'modules.name as name',
            'modules.is_extracurricular as isExtracurricular',
            'curriculums.name as curriculumName',
            DB::raw('count(Distinct courses.id) as coursesCount'),
            DB::raw('sum(courses.credits) as credits'),
            DB::raw('sum(courses.hours_practice) as hoursPractice'),
            DB::raw('sum(courses.hours_theory) as hoursTheory'),
        )
            ->join('courses', 'modules.id', '=', 'courses.module_id')
            ->join('groups', 'courses.id', '=', 'groups.course_id')
            ->join('curriculums', 'modules.curriculum_id', '=', 'curriculums.id')
            ->where('groups.period_id', $periodId)
            ->when('groups.status', 'ABIERTO')
            ->groupby('modules.id')
            ->orderBy('modules.name')
            ->get();
        return $modules;
    }
}
