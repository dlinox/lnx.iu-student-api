<?php

namespace App\Modules\Enrollment\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Course\Models\Course;
use App\Modules\Enrollment\Http\Resources\EnrollmentDataTableItemResource;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
use App\Modules\Group\Models\Group;
use App\Modules\Student\Models\Student;
use App\Modules\Payment\Models\Payment;
use App\Modules\Period\Models\Period;
use App\Modules\Schedule\Models\Schedule;
use App\Services\PaymentService;
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
                DB::raw('CONCAT_WS(" ", people.name, people.last_name_father, people.last_name_mother) as teacher'),
                DB::raw('CONCAT_WS("-", periods.year, view_month_constants.label) as period'),
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
                ->join('view_month_constants', 'periods.month', '=', 'view_month_constants.value')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->leftJoin('people', 'teachers.person_id', '=', 'people.id')
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

            DB::beginTransaction();
            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');

            $period = Period::enrollmentPeriod();

            if (!$period) return  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $paymentData = [
                'studentId' => $student->id,
                'amount' =>  $request->paymentAmount,
                'date' => $request->paymentDate,
                'sequenceNumber' => $request->paymentSequence,
                'paymentTypeId' => $request->paymentMethod,
            ];

            $payment = $this->validatePayment($paymentData);
            $payment = Crypt::decrypt($payment);

            $modulePrice = DB::table('module_prices')
                ->where('module_id', $request->moduleId)
                ->where('student_type_id', $student->student_type_id)
                ->first();

            $totalPayment = Payment::whereIn('id', [$payment])
                ->where('student_id', $student->id)
                ->where('is_used', false)
                ->sum('amount');

            var_dump($student);

            if ($totalPayment < $modulePrice->price) {
                return ApiResponse::error(null, 'El monto del pago no es suficiente para matricularse en el módulo');
            }

            $data = [
                'student_id' => $student->id,
                'module_id' => $request->moduleId,
            ];

            $enrollment = Enrollment::create($data);

            $payment = Payment::find($payment);
            $payment->enrollment_type = 'M';
            $payment->enrollment_id = $enrollment->id;
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

            DB::beginTransaction();

            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error('No se encontró el estudiante', 'No se encontró un estudiante asociado a su usuario');


            $period = Period::enrollmentPeriod();

            if (!$period) return ApiResponse::error('No se encontró el periodo de matrícula', 'No se encontró el periodo de matrícula');

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

            $group = Group::find($request->groupId);

            $groupPrice = DB::table('course_prices')
                ->join('courses', 'course_prices.course_id', '=', 'courses.id')
                ->join('groups', 'courses.id', '=', 'groups.course_id')
                ->where('groups.id', $request->groupId)
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->first();

            $amount = $group->modality == 'PRESENCIAL' ? $groupPrice->presential_price : $groupPrice->virtual_price;

            if ($payment->amount < $amount) {
                return ApiResponse::error('El monto del pago no es suficiente para matricularse en el grupo', 'El monto del pago no es suficiente para matricularse en el grupo');
            }

            $data = [
                'student_id' => $student->id,
                'group_id' => $request->groupId,
                'period_id' => $period->id,
                'created_by' => $user->id,
                'status' => 'MATRICULADO',
            ];

            $enrollmentGroup = EnrollmentGroup::create($data);

            $payment->enrollment_id = $enrollmentGroup->id;
            $payment->enrollment_type = 'G';
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
            DB::beginTransaction();
            $user = Auth::user();
            $student = Student::getStudentByUser($user->model_id);
            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');
            $period = Period::enrollmentPeriod();
            if (!$period) return ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $enrollmentGroup = EnrollmentGroup::find($request->id);
            $newGroup = Group::find($request->groupId);
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
            $period = Period::enrollmentPeriod();
            if (!$period) return ApiResponse::error(null, 'Las reservas no están habilitadas en este periodo');

            $enrollmentGroup = EnrollmentGroup::find($request->id);

            if ($enrollmentGroup->period_id != $period->id) {
                return ApiResponse::error(null, 'No se puede reservar la matrícula en un periodo diferente en el que se matriculó');
            }

            $enrollmentGroup->status = 'RESERVADO';
            $enrollmentGroup->save();

            $payment = Payment::where('enrollment_id', $enrollmentGroup->id)
                ->where('enrollment_type', 'G')
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
            $period = Period::enrollmentPeriod();
            if (!$period) return ApiResponse::error(null, 'No hay periodo de matrícula activo, por lo tanto no se puede cancelar la matrícula');

            $enrollmentGroup = EnrollmentGroup::find($request->id);

            if ($enrollmentGroup->period_id != $period->id) {
                return ApiResponse::error(null, 'No se puede cancelar la matrícula en un periodo diferente en el que se matriculó');
            }

            $enrollmentGroup->status = 'CANCELADO';
            $enrollmentGroup->save();

            $payment = Payment::where('enrollment_id', $enrollmentGroup->id)
                ->where('enrollment_type', 'G')
                ->first();

            if (!$payment) return ApiResponse::error(null, 'No se encontró el pago asociado a la inscripción');

            $payment->is_used = false;
            $payment->enrollment_id = null;
            $payment->enrollment_type = null;
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

            if (!$request->courseId) {
                $course = Course::select('courses.id')
                    ->join('groups', 'courses.id', '=', 'groups.course_id')
                    ->where('groups.id', $request->groupId)
                    ->first();
                if (!$course) return ApiResponse::error(null, 'Parámetros incorrectos, recargue la página e intente nuevamente');
                $request['courseId'] = $course->id;
            }

            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            $period = Period::enrollmentPeriod();

            if (!$period)  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            $enrollmentGroups = Group::select(
                'groups.id',
                'groups.name as group',
                'groups.modality as modality',
                DB::raw('IF(groups.modality = "PRESENCIAL", course_prices.presential_price, course_prices.virtual_price) as price'),
                'laboratories.name as laboratory',
                DB::raw('CONCAT(people.name, " ", people.last_name_father, " ", people.last_name_mother) as teacher'),
                'groups.status as status',
                'groups.max_students as maxStudents',
                'min_students as minStudents',
            )
                ->join('periods', 'groups.period_id', '=', 'periods.id')
                ->join('courses', 'groups.course_id', '=', 'courses.id')
                ->join('course_prices', 'course_prices.course_id', '=', 'courses.id')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->leftJoin('people', 'teachers.person_id', '=', 'people.id')
                ->when($request->groupId, function ($query) use ($request) {
                    return $query->where('groups.id', '!=',   $request->groupId);
                })
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->where('courses.id', $request->courseId)
                ->where('periods.id', $period->id)
                ->whereIn('groups.status', ['ABIERTO', 'CERRADO'])
                ->get()
                ->map(function ($group) {
                    $group['enrolledStudents'] = EnrollmentGroup::where('group_id', $group->id)
                        ->where('status', 'MATRICULADO')
                        ->count();
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
