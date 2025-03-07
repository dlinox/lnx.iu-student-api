<?php

namespace App\Modules\Course\Models;

use App\Modules\Period\Models\Period;
use App\Traits\HasDataTable;
use App\Traits\HasEnabledState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Course extends Model
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

    public static function geCurriculumCourses($curriculum_id, $module_id, $studentId)
    {
        $courses = self::select(
            'courses.id as id',
            'courses.name as name',
            'courses.description as description',
            'courses.is_enabled as isEnabled',
            'courses.code',
            'courses.credits',
            'courses.hours_practice as hoursPractice',
            'courses.hours_theory as hoursTheory',
            'areas.name as area',
            'enrollment_groups.id as hasEnrollmentGroup',
            'groups.name as group',
            DB::raw('CONCAT(periods.year, "-", view_month_constants.label) as period'),
        )
            ->distinct()
            ->leftJoin('groups', 'courses.id', '=', 'groups.course_id')
            ->leftJoin('periods', 'groups.period_id', '=', 'periods.id')
            ->leftJoin('view_month_constants', 'periods.month', '=', 'view_month_constants.value')
            ->leftjoin('enrollment_groups', function ($join) use ($studentId) {
                $join->on('groups.id', '=', 'enrollment_groups.group_id')
                    ->where('enrollment_groups.student_id', $studentId);
            })
            ->join('areas', 'courses.area_id', '=', 'areas.id')
            ->where('courses.curriculum_id', $curriculum_id)
            ->where('courses.module_id', $module_id)
            ->get();

        return $courses;
    }

    //obtener cursos extracurriculares
    public static function getExtracurricularCourses($curriculum_id, $user)
    {

        $courses = self::select(
            'courses.id as id',
            'courses.name as name',
            'courses.description as description',
            'courses.is_enabled as isEnabled',
            'courses.code as code',
            'courses.credits as credits',
            'courses.hours_practice as hoursPractice',
            'courses.hours_theory as hoursTheory',
            'areas.name as area',
            'enrollment_groups.id as hasEnrollmentGroup',
        )
            ->distinct()
            ->join('areas', 'courses.area_id', '=', 'areas.id')
            ->leftJoin('groups', 'courses.id', '=', 'groups.course_id')
            ->join('modules', 'courses.module_id', '=', 'modules.id')
            ->leftJoin('enrollment_groups', function ($join) use ($user) {
                $join->on('groups.id', '=', 'enrollment_groups.group_id')
                    ->where('enrollment_groups.student_id', $user->model_id);
            })
            ->where('courses.curriculum_id', $curriculum_id)
            ->where('modules.is_extracurricular', true)
            ->get();

        return $courses;
    }
}
