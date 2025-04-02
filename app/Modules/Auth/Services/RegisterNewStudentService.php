<?php

namespace App\Modules\Auth\Services;

use App\Constants\StudentTypeConstants;
use App\Http\Responses\ApiResponse;
use App\Mail\SendCodeRegisterMail;
use App\Mail\SendCredentialsMail;
use App\Models\Person;
use App\Models\PreRegister;
use App\Models\Student;
use App\Modules\Auth\Support\AuthSupport;
use App\Modules\User\Models\User;
use App\Services\ApiStudentTypeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class  RegisterNewStudentService
{
    public function __construct() {}

    public function sendVerificationCode($request)
    {
        try {
            $authSupport = new AuthSupport();
            $user = User::where('email', $request->email)->exists();
            if ($user) return ApiResponse::error('', 'El correo ya se encuentra registrado');
            $person = Person::where('email', $request->email)->exists();
            if ($person) return ApiResponse::error('', 'El correo pertenece a una persona, cree una cuenta, si aún no la tiene'); 
            $verificationCode = $authSupport->generateCodeVerification();
            DB::beginTransaction();
            $preRegister = PreRegister::registerItem(['email' => $request->email, 'token' => $verificationCode['encrypted']]);
            Mail::to($request->email)->send(new SendCodeRegisterMail($verificationCode['plain']));
            $payload = encrypt($preRegister->id);
            DB::commit();
            return ApiResponse::success($payload, 'Código de verificación enviado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al enviar código de verificación', 500);
        }
    }

    public function resendVerificationCode($request)
    {
        $item = PreRegister::findItemByPayload($request->payload);
        $request->merge(['email' => $item->email]);
        return $this->sendVerificationCode($request);
    }

    public function verifyCode($request)
    {
        try {
            $item = PreRegister::findItemByPayload($request->payload);
            if (!$item) return ApiResponse::error('', 'No se encontró el registro, vuelva a intentar desde el inicio');
            if (decrypt($item->token) != $request->code) return ApiResponse::error('', 'Código de verificación incorrecto');
            $item->update(['email_verified' => 1]);
            return ApiResponse::success(encrypt($item->id), 'Código de verificación correcto');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al verificar código de verificación', 500);
        }
    }

    public function registerStudentType($request)
    {
        try {
            $item = PreRegister::findItemByPayload($request->payload);
            if (!$item) return ApiResponse::error('', 'No se encontró el registro, vuelva a intentar desde el inicio');

            if ($request->code != null && $request->type == 1) {
                $apiStudentTypeService = new ApiStudentTypeService();
                $responseApi = $apiStudentTypeService->getStudentType($request->code);
                if (!$responseApi['status']) return ApiResponse::error('', $responseApi['error']);

                $studentTypeName = $responseApi['data']['status'];

                $item->update(['student_type' => StudentTypeConstants::getValueByName($studentTypeName), 'student_code' => $request->code]);
                return ApiResponse::success(null, 'Validación de código correcta: ' . $studentTypeName);
            }

            $item->update(['student_type' => StudentTypeConstants::getValueByName('PARTICULAR'), 'student_code' => null]);
            return ApiResponse::success(null, 'Tipo de estudiante registrado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Error al registrar tipo de estudiante', 500);
        }
    }

    public function getStudentByType($id)
    {

        $item = PreRegister::findItemByPayload($id);
        if (!$item) return ApiResponse::error('', 'No se encontró el registro, vuelva a seleccionar el tipo de estudiante');
        if ($item->student_type == 2 && $item->student_code == null) return ApiResponse::success(null);
        $apiStudentTypeService = new ApiStudentTypeService();
        $responseApi = $apiStudentTypeService->getStudentType($item->student_code);
        if (!$responseApi['status']) return ApiResponse::success(null);
        return ApiResponse::success($responseApi['data']);
    }

    public function registerNewStudent($request)
    {
        try {

            $authSupport = new AuthSupport();
            $item = PreRegister::findItemByPayload($request->payload);
  
            if (!$item) return ApiResponse::error('', 'No se encontró el registro, vuelva a intentar desde el inicio');

            $existDocumentNumber = Person::where('document_number', $request->documentNumber)->exists();
            if ($existDocumentNumber) return ApiResponse::error('', 'El número de documento ya se encuentra registrado');

            $existEmail = Person::where('email', $item->email)->exists();
            if ($existEmail) return ApiResponse::error('', 'El correo ya se encuentra registrado');

            $existPhone = Person::where('phone', $request->phone)->exists();
            if ($existPhone) return ApiResponse::error('', 'El número de celular ya se encuentra registrado');

            DB::beginTransaction();

            $request->merge(['email' => $item->email]);
            $person = Person::registerItem($request);

            $student = Student::registerItem($person->id, $item->student_type);

            $password = $authSupport->generatePassword();

            $user = User::registerItem($person, $student->id, $password);
            $user->syncRoles(['estudiante']);

            $userState = $authSupport->userState($user);

            $item->update(['status' => 1]);

            Mail::to($person->email)->send(new SendCredentialsMail(['username' => $person->document_number, 'password' => $password]));

            DB::commit();
            return ApiResponse::success($userState, 'Registro creado correctamente', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 'Error al registrar estudiante', 500);
        }
    }
}
