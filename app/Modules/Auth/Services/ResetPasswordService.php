<?php

namespace App\Modules\Auth\Services;

use App\Http\Responses\ApiResponse;
use App\Mail\ResetPasswordMail;
use App\Modules\Auth\Support\AuthSupport;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;

class  ResetPasswordService
{
    protected $user;
    protected $authSupport;

    public function __construct(User $user)
    {
        $this->authSupport = new AuthSupport();
        $this->user = $user;
    }

    public function execute($request)
    {
        try {
            $password = $this->authSupport->generatePassword();
            $user = $this->user->updatePasswordByEmail($request->email, $password);
            if (!$user) return ApiResponse::error('', 'Usuario no encontrado');
            Mail::to($request->email)->send(new ResetPasswordMail(['password' => $password,]));
            return ApiResponse::success(null, 'Contraseña restablecida correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
