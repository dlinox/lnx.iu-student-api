<?php

namespace App\Modules\Auth\Services;

use App\Http\Responses\ApiResponse;
use App\Mail\CreateAccountMail;
use App\Mail\SendCredentialsMail;
use App\Models\Person;
use App\Models\Student;
use App\Modules\Auth\Support\AuthSupport;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateAccountService
{

    public function sendCreateAccount($request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if ($user) return ApiResponse::error('', 'Ya existe una cuenta con este correo');

            $student = Student::select('students.id')
                ->join('people', 'students.person_id', '=', 'people.id')
                ->where('people.email', $request->email)
                ->first();
            if (!$student) return ApiResponse::error('', 'No se encontró un estudiante con este correo');

            $studenId = encrypt($student->id);

            $url = env('APP_STUDENT_URL') . "/create-account/" . $studenId;

            Mail::to($request->email)->send(new CreateAccountMail($url));

            return ApiResponse::success(null, 'Correo enviado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Ocurrió un error inesperado, por favor intente nuevamente.');
        }
    }

    public function getBasicInformationStudent($id)
    {
        try {
            $item = Student::basicInformation($id);
            if (!$item) return ApiResponse::error('', 'No se encontró el estudiante, reinicie el proceso de registro');
            return ApiResponse::success($item);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getMessage());
        }
    }

    public function createAccount($request)
    {
        try {
            $studentId = decrypt($request->payload);
            $person = Person::getByStudentId($studentId);
            if (!$person) return ApiResponse::error('', 'No se encontró el estudiante, vuelva a intentar desde el inicio');

            $existDocumentNumber = Person::where('document_number', $request->documentNumber)->where('id', '!=', $person->id)->exists();
            if ($existDocumentNumber) return ApiResponse::error('', 'El número de documento ya se encuentra registrado');

            $existEmail = Person::where('email', $person->email)->where('id', '!=', $person->id)->exists();
            if ($existEmail) return ApiResponse::error('', 'El correo ya se encuentra registrado');

            $existPhone = Person::where('phone', $request->phone)->where('id', '!=', $person->id)->exists();
            if ($existPhone) return ApiResponse::error('', 'El teléfono ya se encuentra registrado');
            $authSupport = new AuthSupport();
            DB::beginTransaction();
            $person->updateItem($request);
            $username = $person->document_number;
            $password = $authSupport->generatePassword();
            $user = User::registerItem($person, $studentId, $password);
            $user->syncRoles(['estudiante']);
            $userState = $authSupport->userState($user);
            Mail::to($person->email)->send(new SendCredentialsMail(['username' => $username, 'password' => $password]));
            DB::commit();
            return ApiResponse::success($userState, 'Registro creado correctamente', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Ocurrió un error inesperado, por favor intente nuevamente.');
        }
    }
}
