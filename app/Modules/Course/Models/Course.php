<?php

namespace App\Modules\Course\Models;

use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
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

    public static function geCurriculumCourses($moduleId, $studentId)
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
            DB::raw('count(DISTINCT enrollment_groups.id) as hasEnrollment'),
            DB::raw('GROUP_CONCAT(DISTINCT enrollment_groups.id) as enrollmentGroups'),
        )
            ->distinct()
            ->join('areas', 'courses.area_id', 'areas.id')
            ->leftJoin('groups', 'courses.id', 'groups.course_id')
            ->leftjoin('enrollment_groups', function ($join) use ($studentId) {
                $join->on('groups.id', 'enrollment_groups.group_id')
                    ->where('enrollment_groups.student_id', $studentId);
            })
            ->where('courses.is_enabled', true)
            ->where('courses.module_id', $moduleId)
            ->groupBy('courses.id')
            ->get();

        $courses->map(function ($course) use ($studentId) {

            if ($course->hasEnrollment > 0) {

                $enrollmentGroup = EnrollmentGroup::select(
                    'groups.id as id',
                    'groups.name as group',
                    'groups.modality as modality',
                    'laboratories.name as laboratory',
                    DB::raw('CONCAT_WS(" ", people.name, people.last_name_father, people.last_name_mother) as teacher'),
                    DB::raw('CONCAT_WS(" ", view_month_constants.label, periods.year) as period'),
                )
                    ->distinct()
                    ->join('groups', 'enrollment_groups.group_id', 'groups.id')
                    ->leftJoin('laboratories', 'groups.laboratory_id', 'laboratories.id')
                    ->leftJoin('teachers', 'groups.teacher_id', 'teachers.id')
                    ->leftJoin('people', 'teachers.person_id', 'people.id')
                    ->join('periods', 'groups.period_id', 'periods.id')
                    ->join('view_month_constants', 'periods.month', 'view_month_constants.value')
                    ->whereIn('enrollment_groups.id', explode(',', $course->enrollmentGroups))
                    ->where('enrollment_groups.student_id', $studentId)
                    ->get();

                $enrollmentGroup->map(function ($group) {
                    $schedule = DB::table('schedules')
                        ->select(
                            'schedules.start_hour as startHour',
                            'schedules.end_hour as endHour',
                            DB::raw('GROUP_CONCAT(DISTINCT day) as days')
                        )
                        ->where('schedules.group_id', $group->id)
                        ->groupBy('schedules.start_hour', 'schedules.end_hour')
                        ->first();
                    if ($schedule) {
                        $start = date('H:i a', strtotime($schedule->startHour));
                        $end = date('H:i a', strtotime($schedule->endHour));
                        $days = $schedule->days;
                        $schedule = "{$days} {$start} - {$end}";
                    }
                    $group->schedule = $schedule;

                    return $group;
                });


                $course->enrollment = $enrollmentGroup;
                $course->hasEnrollment = true;
                unset($course->enrollmentGroups);
                return $course;
            } else {
                $course->hasEnrollment = false;
                unset($course->enrollmentGroups);

                return $course;
            }
            //eliminar enrollmentGroups
        });


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
            ->join('areas', 'courses.area_id', 'areas.id')
            ->leftJoin('groups', 'courses.id', 'groups.course_id')
            ->join('modules', 'courses.module_id', 'modules.id')
            ->leftJoin('enrollment_groups', function ($join) use ($user) {
                $join->on('groups.id', 'enrollment_groups.group_id')
                    ->where('enrollment_groups.student_id', $user->model_id);
            })
            ->where('courses.curriculum_id', $curriculum_id)
            ->where('modules.is_extracurricular', true)
            ->get();

        return $courses;
    }
}
