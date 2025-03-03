<?php

namespace App\Modules\Enrollment\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\CurriculumCourse\Models\CurriculumCourse;
use App\Modules\Enrollment\Models\Enrollment;
use App\Modules\EnrollmentGroup\Models\EnrollmentGroup;
use App\Modules\Group\Models\Group;
use App\Modules\Module\Models\Module;
use App\Modules\Student\Models\Student;
use App\Modules\Payment\Models\Payment;
use App\Modules\Period\Models\Period;
use App\Modules\Schedule\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class EnrollmentController extends Controller
{
    //STUDENT ENROLLMENT

    public function storeStudentEnrollment(Request $request)
    {
        try {

            DB::beginTransaction();
            $user = Auth::user();

            $student = Student::select('students.id')
                ->join('people', 'students.person_id', '=', 'people.id')
                ->where('people.document_number', $user->username)
                ->first();

            if (!$student) {
                return ApiResponse::error('No se encontró el estudiante', 'No se encontró el estudiante');
            }

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

            //marcar como utilizado el pago 
            $payment = Payment::find($payment);
            $payment->is_enabled = true;
            $payment->save();

            $data = [
                'student_id' => $student->id,
                'module_id' => $request->moduleId,
                'curriculum_id' => $request->curriculumId,
                'payment_id' => $payment->id,
            ];

            Enrollment::create($data);

            DB::commit();
            return ApiResponse::success(null, 'Matricula exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al guardar la matricula');
        }
    }
    //get enalble groups by user authenticated

    public function enabledGroupsEnrollment(Request $request)
    {
        try {

            $user = Auth::user();

            $student = Student::select(
                'students.id',
                'student_type_id',
            )
                ->join('people', 'students.person_id', '=', 'people.id')
                ->where('people.document_number', $user->username)
                ->first();

            $period = Period::where('is_enabled', true)->first();

            $enrollmentGroups = Group::select(
                'groups.id',
                'groups.name as group',
                'groups.modality as modality',
                DB::raw('IF(groups.modality = "PRESENCIAL", course_prices.presential_price, course_prices.virtual_price) as price'),
                'laboratories.name as laboratory',
                DB::raw('CONCAT(people.name, " ", people.last_name_father, " ", people.last_name_mother) as teacher'),
            )
                ->join('periods', 'groups.period_id', '=', 'periods.id')
                ->join('curriculum_courses', 'groups.curriculum_course_id', '=', 'curriculum_courses.id')
                ->join('course_prices', 'course_prices.course_id', '=', 'curriculum_courses.course_id')
                ->leftJoin('laboratories', 'groups.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('teachers', 'groups.teacher_id', '=', 'teachers.id')
                ->leftJoin('people', 'teachers.person_id', '=', 'people.id')
                ->where('course_prices.student_type_id', $student->student_type_id)
                ->where('curriculum_courses.id', $request->curriculumCourseId)
                ->where('periods.id', $period->id)
                ->where('groups.is_enabled', true)
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

            $student = Student::select('students.id')
                ->join('people', 'students.person_id', '=', 'people.id')
                ->where('people.document_number', $user->username)
                ->first();

            if (!$student) {
                return ApiResponse::error('No se encontró el estudiante', 'No se encontró un estudiante asociado a su usuario');
            }

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

            //marcar como utilizado el pago 
            $payment = Payment::find($payment);
            $payment->is_enabled = true;
            $payment->save();

            $period = Period::where('is_enabled', true)
                ->where('enrollment_enabled', true)
                ->first();

            if (!$period) {
                return ApiResponse::error('No se encontró el periodo de matrícula', 'No se encontró el periodo de matrícula');
            }

            $data = [
                'student_id' => $student->id,
                'group_id' => $request->groupId,
                'period_id' => $period->id,
                'payment_id' => $payment->id,
            ];

            EnrollmentGroup::create($data);

            DB::commit();
            return ApiResponse::success(null, 'Matricula exitosa');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al cargar los registros');
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
        $validate = $this->_validatePaymentService($data);

        if (!$validate) {
            throw new \Exception('Error al validar el pago');
        }
        $payment = Payment::where('amount', $data['amount'])
            ->where('date', $data['date'])
            ->where('sequence_number', $data['sequenceNumber'])
            // ->where('payment_type_id', $data['paymentTypeId'])
            // ->where('student_id', $data['studentId'])
            ->first();

        if ($payment && $payment->student_id != $data['studentId']) {
            throw new \Exception('El pago ya fue registrado por otro estudiante');
        }

        if ($payment && $payment->is_enabled) {
            throw new \Exception('El pago ya fue utilizado');
        }

        if (!$payment) {
            $payment = Payment::create([
                'student_id' => $data['studentId'],
                'sequence_number' => $data['sequenceNumber'],
                'payment_type_id' => $data['paymentTypeId'],
                'amount' => $data['amount'],
                'date' => $data['date'],
                'is_enabled' => false,
            ]);
        }

        $paymentToken = Crypt::encrypt($payment->id);

        return $paymentToken;
    }

    private function _validatePaymentService($data)
    {
        return  true;
    }
}
