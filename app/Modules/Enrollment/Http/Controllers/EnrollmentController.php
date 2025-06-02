<?php

namespace App\Modules\Enrollment\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CoursePrice;
use App\Models\ModulePrice;
use App\Modules\Course\Models\Course;
use App\Modules\Enrollment\Http\Resources\EnrollmentDataTableItemResource;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\EnrollmentDeadline\Models\EnrollmentDeadline;
use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
use App\Modules\Group\Models\Group;
use App\Models\Student;
use App\Modules\Module\Models\Module;
use App\Modules\Payment\Models\Payment;
use App\Modules\Schedule\Models\Schedule;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{

    public function loadDataTable(Request $request)
    {
        try {
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            $items = EnrollmentGroup::select(
                'enrollment_groups.id as id',
                'modules.name as module',
                'enrollment_groups.status as enrollmentStatus',
                'groups.id as groupId',
                'groups.name as group',
                'groups.modality as modality',
                'laboratories.name as laboratory',
                DB::raw('CONCAT_WS(" ", teachers.name, teachers.last_name_father, teachers.last_name_mother) as teacher'),
                DB::raw('CONCAT_WS("-", periods.year, UPPER(months.name)) as period'),
                DB::raw('CONCAT_WS("- ", courses.code, courses.name) as course'),
                'courses.code as code',
                'courses.credits as credits',
                'courses.hours_practice as hoursPractice',
                'courses.hours_theory as hoursTheory',
                'areas.name as area',
            )
                ->join('groups', 'enrollment_groups.group_id', '=', 'groups.id')
                ->join('courses', 'groups.course_id', '=', 'courses.id')
                ->join('modules', 'courses.module_id', '=', 'modules.id')
                ->join('areas', 'courses.area_id', '=', 'areas.id')
                ->join('periods', 'enrollment_groups.period_id', '=', 'periods.id')
                ->join('months', 'periods.month', '=', 'months.id')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->where('student_id', $student->id)
                ->orderBy('periods.year', 'desc')
                ->orderBy('periods.month', 'desc')
                ->dataTable($request);
            EnrollmentDataTableItemResource::collection($items);
            return ApiResponse::success($items);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    public function storeStudentEnrollment(Request $request)
    {
        try {

            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');

            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $modulePrice = DB::table('module_prices')
                ->where('module_id', $request->moduleId)
                ->where('student_type_id', $student->student_type_id)
                ->first();

            $module = Module::select('modules.id', 'modules.is_extracurricular')
                ->where('modules.id', $request->moduleId)
                ->first();

            $group = Group::where('id', $request->groupId)
                ->first();

            if (!$group) return ApiResponse::error(null, 'No se encontró el grupo seleccionado');

            $shedules = Schedule::where('group_id', $group->id)->get();
            if ($shedules->count() == 0) {
                return ApiResponse::error(null, 'El grupo no tiene horarios asignados');
            }

            $enrolledSchedules = Schedule::select('schedules.id', 'schedules.start_hour', 'schedules.end_hour', 'schedules.day')
                ->join('groups', 'schedules.group_id', '=', 'groups.id')
                ->join('enrollment_groups', 'groups.id', '=', 'enrollment_groups.group_id')
                ->where('enrollment_groups.student_id', $student->id)
                ->where('enrollment_groups.period_id', $enrollmentPeriod['periodId'])
                ->where('enrollment_groups.status', 'MATRICULADO')
                ->get();

            foreach ($shedules as $shedule) {
                foreach ($enrolledSchedules as $enrolledShedule) {
                    if ($shedule->day == $enrolledShedule->day) {
                        $startHour = strtotime($shedule->start_hour);
                        $endHour = strtotime($shedule->end_hour);
                        $enrolledStartHour = strtotime($enrolledShedule->start_hour);
                        $enrolledEndHour = strtotime($enrolledShedule->end_hour);

                        if ($startHour < $enrolledEndHour && $endHour > $enrolledStartHour) {
                            return ApiResponse::error(null, 'Este grupo tiene un horario que se cruza con otro curso en el que ya estás inscrito. Por favor, revisa tus horarios.');
                        }
                    }
                }
            }

            $coursePrice = DB::table('course_prices')
                ->join('courses', 'course_prices.course_id', '=', 'courses.id')
                ->where('courses.id', $group->course_id)
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->first();

            if (!$coursePrice) return ApiResponse::error(null, 'No se encontró el precio del curso');

            $monthlyPrice = $group->modality == 'PRESENCIAL' ? $coursePrice->presential_price : $coursePrice->virtual_price;

            $totalPrice = $modulePrice->price + $monthlyPrice;

            $paymentData = [
                'studentId' => $student->id,
                'amount' =>  $request->paymentAmount,
                'date' => $request->paymentDate,
                'sequenceNumber' => $request->paymentSequence,
                'paymentTypeId' => $request->paymentMethod,
            ];

            $payment = $this->validatePayment($paymentData);

            $payment = Crypt::decrypt($payment);

            $totalPayment = Payment::whereIn('id', [$payment])
                ->where('student_id', $student->id)
                ->where('is_used', false)
                ->sum('amount');

            if ($totalPayment < $totalPrice) {
                return ApiResponse::error(null, 'El monto del pago no es suficiente para matricularse en el módulo');
            }

            $data = [
                'student_id' => $student->id,
                'module_id' => $request->moduleId,
            ];

            $dataGroup = [
                'student_id' => $student->id,
                'group_id' => $request->groupId,
                'period_id' => $enrollmentPeriod['periodId'],
                'created_by' => $user->id,
                'enrollment_modality' => 'VIRTUAL',
                'status' => 'MATRICULADO',
                'with_enrollment' => $module->is_extracurricular == 1 ? false : true,
                'special_enrollment' => $module->is_extracurricular == 1 ? true : false,
            ];

            DB::beginTransaction();

            Enrollment::create($data);
            $enrollmentGroup = EnrollmentGroup::create($dataGroup);

            $payment = Payment::find($payment);
            $payment->enrollment_id = $enrollmentGroup->id;
            $payment->is_used = true;
            $payment->save();
            DB::commit();
            return ApiResponse::success(null, 'Matricula exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al guardar la matricula');
        }
    }

    public function storeGroupEnrollment(Request $request)
    {
        try {

            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error('No se encontró el estudiante', 'No se encontró un estudiante asociado a su usuario');

            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $paymentData = [
                'studentId' => $student->id,
                'amount' => (float) $request->paymentAmount,
                'date' => $request->paymentDate,
                'sequenceNumber' => $request->paymentSequence,
                'paymentTypeId' => $request->paymentMethod,
            ];

            $payment = $this->validatePayment($paymentData);
            $payment = Crypt::decrypt($payment);

            $payment = Payment::find($payment);

            //vericar cruces de horarios
            $group = Group::find($request->groupId);
            $shedules = Schedule::where('group_id', $group->id)->get();
            if ($shedules->count() == 0) {
                return ApiResponse::error(null, 'El grupo no tiene horarios asignados');
            }

            $enrolledSchedules = Schedule::select('schedules.id', 'schedules.start_hour', 'schedules.end_hour', 'schedules.day')
                ->join('groups', 'schedules.group_id', '=', 'groups.id')
                ->join('enrollment_groups', 'groups.id', '=', 'enrollment_groups.group_id')
                ->where('enrollment_groups.student_id', $student->id)
                ->where('enrollment_groups.period_id', $enrollmentPeriod['periodId'])
                ->where('enrollment_groups.status', 'MATRICULADO')
                ->get();

            //verificamos que no haya cruce de horarios
            foreach ($shedules as $shedule) {
                foreach ($enrolledSchedules as $enrolledShedule) {
                    if ($shedule->day == $enrolledShedule->day) {
                        $startHour = strtotime($shedule->start_hour);
                        $endHour = strtotime($shedule->end_hour);
                        $enrolledStartHour = strtotime($enrolledShedule->start_hour);
                        $enrolledEndHour = strtotime($enrolledShedule->end_hour);

                        if ($startHour < $enrolledEndHour && $endHour > $enrolledStartHour) {
                            return ApiResponse::error(null, 'Este grupo tiene un horario que se cruza con otro curso en el que ya estás inscrito. Por favor, revisa tus horarios.');
                        }
                    }
                }
            }

            $groupPrice = DB::table('course_prices')
                ->join('courses', 'course_prices.course_id', '=', 'courses.id')
                ->join('groups', 'courses.id', '=', 'groups.course_id')
                ->where('groups.id', $request->groupId)
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->first();

            $amount = $group->modality == 'PRESENCIAL' ? $groupPrice->presential_price : $groupPrice->virtual_price;

            if (!$groupPrice) return ApiResponse::error(null, 'No se encontró el precio del curso');

            if ($request->isSpecial) {
                $specialPrice = CoursePrice::select('presential_price', 'virtual_price')
                    ->join('courses', 'courses.id', '=', 'course_prices.course_id')
                    ->join('modules', 'modules.id', '=', 'courses.module_id')
                    ->where('course_prices.student_type_id', $student->student_type_id)
                    ->where('modules.is_extracurricular', true)
                    ->first();

                if (!$specialPrice) return ApiResponse::error(null, 'No se encontró el precio para el tipo de estudiante y el curso');

                $amount = $group->modality == 'PRESENCIAL' ? $specialPrice->presential_price : $specialPrice->virtual_price;
            }

            $withEnrollment = false;

            if ($request->isSpecial == false) {
                $module = Module::select('modules.id as moduleId', 'modules.name as moduleName')
                    ->join('courses', 'courses.module_id', '=', 'modules.id')
                    ->where('courses.id', $group->course_id)
                    ->first();

                $lastEnrollment = EnrollmentGroup::select(
                    'periods.year',
                    'periods.month',
                )
                    ->join('groups', 'enrollment_groups.group_id', '=', 'groups.id')
                    ->join('courses', 'groups.course_id', '=', 'courses.id')
                    ->join('periods', 'enrollment_groups.period_id', '=', 'periods.id')
                    ->where('enrollment_groups.student_id', $student->id)
                    ->where('courses.module_id', $module->moduleId)
                    ->where('enrollment_groups.special_enrollment', false)
                    ->where('enrollment_groups.with_enrollment', true)
                    ->where('enrollment_groups.status', 'MATRICULADO')
                    ->orderBy('periods.id', 'desc')
                    ->first();

                if ($lastEnrollment) {
                    $lastEnrollmentDate = Carbon::createFromFormat('Y-m', $lastEnrollment->year . '-' . $lastEnrollment->month);
                    $currentDate = Carbon::now();
                    if ($lastEnrollmentDate->diffInMonths($currentDate) > 12) {

                        $modulePrice = ModulePrice::select('module_prices.price')
                            ->where('module_prices.module_id', $module->moduleId)
                            ->where('module_prices.student_type_id', $student->student_type_id)
                            ->first();

                        if (!$modulePrice) return ApiResponse::error(null, 'No se encontró el precio del módulo');

                        $enrollmentPrice = (float) $modulePrice->price;
                        $amount += $enrollmentPrice;
                        $withEnrollment = true;
                    }
                } else {
                    $modulePrice = ModulePrice::select('module_prices.price')
                        ->where('module_prices.module_id', $module->moduleId)
                        ->where('module_prices.student_type_id', $student->student_type_id)
                        ->first();

                    if (!$modulePrice) return ApiResponse::error(null, 'No se encontró el precio del módulo');

                    $enrollmentPrice = (float) $modulePrice->price;
                    $amount += $enrollmentPrice;
                    $withEnrollment = true;
                }
            }


            if ($payment->amount < $amount) {
                return ApiResponse::error('El monto del pago no es suficiente para matricularse en el grupo', 'El monto del pago no es suficiente para matricularse en el grupo');
            }

            $data = [
                'student_id' => $student->id,
                'group_id' => $request->groupId,
                'period_id' => $enrollmentPeriod['periodId'],
                'created_by' => $user->id,
                'enrollment_modality' => 'VIRTUAL',
                'status' => 'MATRICULADO',
                'special_enrollment' => isset($request->isSpecial) ? $request->isSpecial : false,
                'with_enrollment' => $withEnrollment,
            ];

            DB::beginTransaction();
            $enrollmentGroup = EnrollmentGroup::create($data);
            $payment->enrollment_id = $enrollmentGroup->id;
            $payment->is_used = true;
            $payment->save();
            DB::commit();
            return ApiResponse::success(null, 'Inscripción exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al guardar la inscripción');
        }
    }

    public function updateGroupEnrollment(Request $request)
    {
        try {
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');

            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $enrollmentGroup = EnrollmentGroup::find($request->id);

            $newGroup = Group::find($request->groupId);

            $shedules = Schedule::where('group_id', $newGroup->id)->get();
            if ($shedules->count() == 0) {
                return ApiResponse::error(null, 'El grupo no tiene horarios asignados');
            }

            $enrolledSchedules = Schedule::select('schedules.id', 'schedules.start_hour', 'schedules.end_hour', 'schedules.day')
                ->join('groups', 'schedules.group_id', '=', 'groups.id')
                ->join('enrollment_groups', 'groups.id', '=', 'enrollment_groups.group_id')
                ->where('enrollment_groups.student_id', $student->id)
                ->where('enrollment_groups.period_id', $enrollmentPeriod['periodId'])
                ->where('groups.id', '!=', $enrollmentGroup->group_id)
                ->where('enrollment_groups.status', 'MATRICULADO')
                ->get();

            //verificamos que no haya cruce de horarios
            foreach ($shedules as $shedule) {
                foreach ($enrolledSchedules as $enrolledShedule) {
                    if ($shedule->day == $enrolledShedule->day) {
                        $startHour = strtotime($shedule->start_hour);
                        $endHour = strtotime($shedule->end_hour);
                        $enrolledStartHour = strtotime($enrolledShedule->start_hour);
                        $enrolledEndHour = strtotime($enrolledShedule->end_hour);

                        if ($startHour < $enrolledEndHour && $endHour > $enrolledStartHour) {
                            return ApiResponse::error(null, 'Este grupo tiene un horario que se cruza con otro curso en el que ya estás inscrito. Por favor, revisa tus horarios.');
                        }
                    }
                }
            }

            $newGroupPrice = DB::table('course_prices')
                ->join('courses', 'course_prices.course_id', '=', 'courses.id')
                ->join('groups', 'courses.id', '=', 'groups.course_id')
                ->where('groups.id', $request->groupId)
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->first();
            $currentGroup = Group::find($enrollmentGroup->group_id);

            $currentGroupPrice = DB::table('course_prices')
                ->join('courses', 'course_prices.course_id', '=', 'courses.id')
                ->join('groups', 'courses.id', '=', 'groups.course_id')
                ->where('groups.id', $enrollmentGroup->group_id)
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->first();

            $newPrice = $newGroup->modality == 'PRESENCIAL' ? $newGroupPrice->presential_price : $newGroupPrice->virtual_price;
            $currentPrice = $currentGroup->modality == 'PRESENCIAL' ? $currentGroupPrice->presential_price : $currentGroupPrice->virtual_price;

            if ($newPrice > $currentPrice) {
                return ApiResponse::error(null, 'El monto del nuevo grupo es mayor al monto del grupo actual');
            }

            DB::beginTransaction();
            $enrollmentGroup = EnrollmentGroup::find($request->id);
            $enrollmentGroup->group_id = $request->groupId;
            $enrollmentGroup->save();
            DB::commit();

            return ApiResponse::success(null, 'Cambio de grupo exitoso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al guardar la inscripción');
        }
    }

    public function reserverGroupEnrollment(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');

            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $enrollmentGroup = EnrollmentGroup::find($request->id);

            if ($enrollmentGroup->period_id != $enrollmentPeriod['periodId']) {
                return ApiResponse::error(null, 'No se puede reservar la matrícula en un periodo diferente en el que se matriculó');
            }

            $enrollmentGroup->status = 'RESERVADO';
            $enrollmentGroup->save();

            $payment = Payment::where('enrollment_id', $enrollmentGroup->id)
                ->first();

            if (!$payment) return ApiResponse::error(null, 'No se encontró el pago asociado a la inscripción');

            DB::commit();
            return ApiResponse::success(null, 'Reserva exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al realizar la reserva');
        }
    }

    public function cancelGroupEnrollment(Request $request)
    {

        try {
            DB::beginTransaction();
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');


            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $enrollmentGroup = EnrollmentGroup::find($request->id);

            if ($enrollmentGroup->period_id != $enrollmentPeriod['periodId']) {
                return ApiResponse::error(null, 'No se puede cancelar la matrícula en un periodo diferente en el que se matriculó');
            }

            $enrollmentGroup->status = 'CANCELADO';
            $enrollmentGroup->with_enrollment = false;
            $enrollmentGroup->save();

            $payment = Payment::where('enrollment_id', $enrollmentGroup->id)
                ->first();

            if (!$payment) return ApiResponse::error(null, 'No se encontró el pago asociado a la inscripción');

            $payment->is_used = false;
            $payment->enrollment_id = null;
            $payment->save();
            DB::commit();
            return ApiResponse::success(null, 'Cancelación exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al realizar la cancelación');
        }
    }

    public function enabledGroupsEnrollment(Request $request)
    {
        try {

            $enrollmentPeriod = EnrollmentDeadline::activeEnrollmentPeriod();
            if (!$enrollmentPeriod)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            if (!$request->courseId) {
                $course = Course::select('courses.id')
                    ->join('groups', 'courses.id', '=', 'groups.course_id')
                    ->where('groups.id', $request->groupId)
                    ->first();
                if (!$course) return ApiResponse::error(null, 'Parámetros incorrectos, recargue la página e intente nuevamente');

                $enrollmentGroups = EnrollmentGroup::where('student_id', $student->id)
                    ->where('period_id', $enrollmentPeriod['periodId'])
                    ->where('group_id', $request->groupId)
                    ->where('status', 'MATRICULADO')
                    ->first();

                if ($enrollmentGroups) {
                    $request['isSpecial'] = $enrollmentGroups->special_enrollment;
                }

                $request['courseId'] = $course->id;
            }



            $module = Module::select('modules.id as moduleId', 'modules.name as moduleName')
                ->join('courses', 'courses.module_id', '=', 'modules.id')
                ->where('courses.id', $request->courseId)
                ->first();


            $requireEnrollmentPrice = false;

            if ($request->isSpecial == false) {
                $lastEnrollment = EnrollmentGroup::select(
                    'periods.year',
                    'periods.month',
                )
                    ->join('groups', 'enrollment_groups.group_id', '=', 'groups.id')
                    ->join('courses', 'groups.course_id', '=', 'courses.id')
                    ->join('periods', 'enrollment_groups.period_id', '=', 'periods.id')
                    ->where('enrollment_groups.student_id', $student->id)
                    ->where('courses.module_id', $module->moduleId)
                    ->where('enrollment_groups.special_enrollment', false)
                    ->where('enrollment_groups.with_enrollment', true)
                    ->orderBy('periods.id', 'desc')
                    ->first();

                if ($lastEnrollment) {
                    $lastEnrollmentDate = Carbon::createFromFormat('Y-m', $lastEnrollment->year . '-' . $lastEnrollment->month);
                    $currentDate = Carbon::now();
                    if ($lastEnrollmentDate->diffInMonths($currentDate) > 12) {
                        $requireEnrollmentPrice = true;
                    }
                } else {
                    $requireEnrollmentPrice = true;
                }
            }

            $enrollmentGroups = Group::select(
                'groups.id',
                'groups.name as group',
                'groups.modality as modality',
                DB::raw('IF(groups.modality = "PRESENCIAL", course_prices.presential_price, course_prices.virtual_price) as price'),
                'laboratories.name as laboratory',
                DB::raw('CONCAT(teachers.name, " ", teachers.last_name_father, " ", teachers.last_name_mother) as teacher'),
                'groups.status as status',
                'groups.max_students as maxStudents',
                'min_students as minStudents',
            )
                ->join('periods', 'groups.period_id', '=', 'periods.id')
                ->join('courses', 'groups.course_id', '=', 'courses.id')
                ->join('course_prices', 'course_prices.course_id', '=', 'courses.id')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->when($request->groupId, function ($query) use ($request) {
                    return $query->where('groups.id', '!=', $request->groupId);
                })
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->where('courses.id', $request->courseId)
                ->where('periods.id', $enrollmentPeriod['periodId'])
                ->whereIn('groups.status', ['ABIERTO'])
                ->get()
                ->map(function ($group) use ($request, $student, $enrollmentPeriod, $module, $requireEnrollmentPrice) {

                    $group['enrolledStudents'] = EnrollmentGroup::where('group_id', $group->id)
                        ->where('status', 'MATRICULADO')
                        ->count();

                    if ($request->isSpecial) {
                        $specialPrice = CoursePrice::select('presential_price', 'virtual_price')
                            ->join('courses', 'courses.id', '=', 'course_prices.course_id')
                            ->join('modules', 'modules.id', '=', 'courses.module_id')
                            ->where('course_prices.student_type_id', $student->student_type_id)
                            ->where('modules.is_extracurricular', true)
                            ->first();

                        if (!$specialPrice) return ApiResponse::error(null, 'No se encontró el precio para el tipo de estudiante y el curso');

                        $price = $group->modality == 'PRESENCIAL' ? $specialPrice->presential_price : $specialPrice->virtual_price;
                        $group['price'] = number_format($price, 2);
                    } else {
                        $withEnrollment = EnrollmentGroup::select('with_enrollment')
                            ->join('groups', 'enrollment_groups.group_id', '=', 'groups.id')
                            ->where('enrollment_groups.student_id', $student->id)
                            ->where('enrollment_groups.period_id', $enrollmentPeriod['periodId'])
                            ->where('groups.course_id', $request->courseId)
                            ->where('enrollment_groups.status', 'MATRICULADO')
                            ->first();
                        if ($withEnrollment) {
                            if ($withEnrollment->with_enrollment) {
                                $requireEnrollmentPrice = true;
                            }
                        }
                    }

                    if ($requireEnrollmentPrice) {

                        $modulePrice = ModulePrice::select('module_prices.price')
                            ->where('module_prices.module_id', $module->moduleId)
                            ->where('module_prices.student_type_id', $student->student_type_id)
                            ->first();

                        if (!$modulePrice) return ApiResponse::error(null, 'No se encontró el precio del módulo');

                        $enrollmentPrice = (float) $modulePrice->price;

                        $group['price'] += $enrollmentPrice;
                        $group['price'] = number_format($group['price'], 2);
                    }

                    $group['schedules'] = Schedule::byGroup($group->id);
                    return $group;
                });

            return ApiResponse::success($enrollmentGroups);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
        }
    }

    private function validatePayment($data)
    {
        $paymentService = new PaymentService();
        $validate = $paymentService::validatePaymentBank($data);

        if (!$validate) {
            throw new \Exception('El pago no es válido, verifique los datos y asegúrese de la fecha de pago sea con un día de antisipación');
        }
        $payment = Payment::where('amount', $data['amount'])
            ->where('date', $data['date'])
            ->where('sequence_number', $data['sequenceNumber'])
            // ->where('payment_type_id', $data['paymentTypeId'])
            // ->where('student_id', $data['studentId'])
            ->first();

        if ($payment) {
            if ($payment->student_id != $data['studentId']) throw new \Exception('El pago ya fue registrado por otro estudiante');
            if ($payment->is_used == true) throw new \Exception('El pago ya fue utilizado');
        } else {
            $payment = Payment::registerItem($data);
        }

        $paymentToken = Crypt::encrypt($payment->id);

        return $paymentToken;
    }
}
