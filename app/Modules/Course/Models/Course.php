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

    public static function getCurriculumCourses($moduleId, $studentId)
    {
        $courses = self::select(
            'courses.id as id',
            'courses.name as name',
            'courses.description as description',
            'courses.is_enabled as isEnabled',
            'courses.code',
            'areas.name as area',
            DB::raw('count(DISTINCT enrollment_groups.id) as hasEnrollment'),
            DB::raw('GROUP_CONCAT(DISTINCT enrollment_groups.id) as enrollmentGroups'),
            DB::raw('GROUP_CONCAT(DISTINCT enrollment_groups.status) as enrollmentStatus'),
        )
            ->distinct()
            ->join('areas', 'courses.area_id', 'areas.id')
            ->leftJoin('groups', 'courses.id', 'groups.course_id')
            ->leftjoin('enrollment_groups', function ($join) use ($studentId) {
                $join->on('groups.id', 'enrollment_groups.group_id')
                    ->where('enrollment_groups.student_id', $studentId)
                    ->where('enrollment_groups.status', '!=', 'CANCELADO');
            })
            ->where('courses.is_enabled', true)
            ->where('courses.module_id', $moduleId)
            ->groupBy('courses.id')
            ->orderByDesc('hasEnrollment')
            ->get();

        $courses->map(function ($course) use ($studentId) {

            if ($course->hasEnrollment > 0) {

                $enrollmentGroup = EnrollmentGroup::select(
                    'groups.id as id',
                    'groups.name as group',
                    'groups.min_students as minStudents',
                    'groups.modality as modality',
                    'laboratories.name as laboratory',
                    'laboratories.virtual_link as virtualLink',
                    DB::raw('CONCAT_WS(" ", teachers.name, teachers.last_name_father, teachers.last_name_mother) as teacher'),
                    DB::raw('CONCAT_WS(" ", UPPER(months.name), periods.year) as period'),
                    'periods.id as periodId',
                    'enrollment_groups.status as enrollmentStatus',
                    'enrollment_grades.grade',
                )
                    ->distinct()
                    ->join('groups', 'enrollment_groups.group_id', 'groups.id')
                    ->leftJoin('laboratories', 'groups.laboratory_id', 'laboratories.id')
                    ->leftJoin('teachers', 'groups.teacher_id', 'teachers.id')
                    ->leftJoin('enrollment_grades', 'enrollment_groups.id', 'enrollment_grades.enrollment_group_id')
                    ->join('periods', 'groups.period_id', 'periods.id')
                    ->join('months', 'periods.month', 'months.id')
                    ->whereIn('enrollment_groups.id', explode(',', $course->enrollmentGroups))
                    ->where('enrollment_groups.student_id', $studentId)
                    ->orderBy('groups.id', 'asc')
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

                    $group->countStudents = DB::table('enrollment_groups')
                        ->where('enrollment_groups.group_id', $group->id)
                        ->where('enrollment_groups.status', 'MATRICULADO')
                        ->count();

                    return $group;
                });

                $course->lastEnrollment = $enrollmentGroup[count($enrollmentGroup) - 1]->periodId;
                $course->isApproved = $enrollmentGroup->where('grade', '>=', 11)->count() > 0;
                $course->enrollment = $enrollmentGroup;
                $course->hasEnrollment = true;
                unset($course->enrollmentGroups);
                return $course;
            } else {

                $course->lastEnrollment = null;
                $course->isApproved = false;
                $course->hasEnrollment = false;
                unset($course->enrollmentGroups);

                return $course;
            }
            //eliminar enrollmentGroups
        });


        return $courses;
    }

    public static function getEnabledGroups($periodId)
    {
        $groups = self::select(
            'groups.id',
            'groups.name',
            'groups.min_students as minStudents',
            'courses.id as courseId',
            'courses.name as courseName',
            'areas.name as areaName',
            'modules.id as moduleId',
            'modules.name as moduleName',
        )
            ->distinct()
            ->join('areas', 'courses.area_id', 'areas.id')
            ->join('groups', 'courses.id', 'groups.course_id')
            ->join('modules', 'courses.module_id', 'modules.id')
            ->where('groups.period_id', $periodId)
            ->whereIn('groups.status', ['ABIERTO'])
            ->get()->map(function ($group) {
                $group->countStudents = DB::table('enrollment_groups')
                    ->where('enrollment_groups.group_id', $group->id)
                    ->where('enrollment_groups.status', 'MATRICULADO')
                    ->count();

                return $group;
            });

            //order by countStudents
        $groups = $groups->sortByDesc('countStudents')->values()->all();


        return $groups;
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

    public static function getCoursesEnabled($periodId, $limit = 10)
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
            'modules.name as module',
            'modules.id as moduleId',
            'modules.is_extracurricular as isExtracurricular',
        )
            ->distinct()
            ->join('areas', 'courses.area_id', 'areas.id')
            ->join('modules', 'courses.module_id', 'modules.id')
            ->join('groups', 'courses.id', 'groups.course_id')
            ->where('groups.period_id', $periodId)
            ->where('courses.is_enabled', true)
            ->whereIn('groups.status', ['ABIERTO'])
            ->limit($limit)
            ->get();

        return $courses;
    }

    public static function getByModuleForSelect($moduleId)
    {
        $courses = self::select(
            'courses.order',
            'courses.id as value',
            'courses.name as title',
        )
            ->distinct()
            ->where('courses.module_id', $moduleId)
            ->where('courses.is_enabled', true)
            ->orderBy('courses.order')
            ->get();

        return $courses ? $courses : [];
    }
}
