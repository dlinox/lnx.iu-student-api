<?php

namespace App\Modules\Enrollment\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
use App\Modules\Group\Models\Group;
use App\Modules\Student\Models\Student;
use App\Modules\Payment\Models\Payment;
use App\Modules\Period\Models\Period;
use App\Modules\Schedule\Models\Schedule;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{

    public function storeStudentEnrollment(Request $request)
    {
        try {

            DB::beginTransaction();
            $user = Auth::user();

            $student = Student::getStudentByUser($user->model_id);

            if (!$student) return ApiResponse::error(null, 'No se encontró un estudiante asociado a su usuario');

            $period = Period::enrollmentPeriod();

            if (!$period) return  ApiResponse::error(null, 'No se encontró el periodo de matrícula');

            //validar pago
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

    public function enabledGroupsEnrollment(Request $request)
    {
        try {

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
            )

                ->join('periods', 'groups.period_id', '=', 'periods.id')
                ->join('courses', 'groups.course_id', '=', 'courses.id')
                ->join('course_prices', 'course_prices.course_id', '=', 'courses.id')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->leftJoin('people', 'teachers.person_id', '=', 'people.id')
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->where('courses.id', $request->courseId)
                ->where('periods.id', $period->id)
                ->whereIn('groups.status', ['ABIERTO', 'CERRADO'])
                ->get()
                ->map(function ($group) use ($request) {
                    $group['schedules'] = Schedule::select(
                        'schedules.day as day',
                        'schedules.start_hour as startHour',
                        'schedules.end_hour as endHour',
                    )
                        ->where('schedules.group_id', $group->id)
                        ->get();
                    return $group;
                });

            return ApiResponse::success($enrollmentGroups);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
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

            //validar pago
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

    public function validatePaymentEnrollment(Request $request)
    {

        $request['date'] = Carbon::createFromTimestampMs($request->date)->format('Y-m-d');
        $payment = $this->validatePayment($request->all());

        return ApiResponse::success($payment, 'Pago validado correctamente');
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

    private function _validatePaymentService($data)
    {
        return  true;
    }
}
