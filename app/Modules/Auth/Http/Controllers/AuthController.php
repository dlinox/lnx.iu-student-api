<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Auth\Services\CreateAccountService;
use App\Modules\Auth\Services\RegisterNewStudentService;
use App\Modules\Auth\Services\ResetPasswordService;
use App\Modules\Auth\Services\SignInService;
use App\Modules\Auth\Support\AuthSupport;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function signIn(Request $request)
    {
        $signInService = new SignInService($this->user);
        return $signInService->execute($request);
    }

    public function resetPassword(Request $request)
    {
        $resetPasswordService = new ResetPasswordService($this->user);
        return $resetPasswordService->execute($request);
    }

    public function sendVerificationCode(Request $request)
    {
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->sendVerificationCode($request);
    }

    public function resendVerificationCode(Request $request)
    {
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->resendVerificationCode($request);
    }

    public function verifyCode(Request $request)
    {
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->verifyCode($request);
    }

    public function registerStudentType(Request $request)
    {
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->registerStudentType($request);
    }

    public function registerNewStudent(Request $request)
    {
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->registerNewStudent($request);
    }

    public function getStudentByType(Request $request)
    {
        $id = $request->payload;
        $registerNewStudentService = new RegisterNewStudentService();
        return $registerNewStudentService->getStudentByType($id);
    }

    public function sendCreateAccount(Request $request)
    {
        $createAccountService = new CreateAccountService();
        return $createAccountService->sendCreateAccount($request);
    }

    public function getBasicInformationStudent(Request $request)
    {
        $id = decrypt($request->payload);
        $createAccountService = new CreateAccountService();
        return $createAccountService->getBasicInformationStudent($id);
    }



    public function createAccount(Request $request)
    {
        $createAccountService = new CreateAccountService();
        return $createAccountService->createAccount($request);
    }

    public function signOut(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success(null, 'Hasta luego');
    }

    public function user(Request $request)
    {
        $authSupport = new AuthSupport();
        $user =  $request->user();
        $userState = $authSupport->userState($user);
        return ApiResponse::success($userState);
    }
}
