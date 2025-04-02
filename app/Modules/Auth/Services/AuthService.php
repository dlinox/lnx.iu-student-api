<?php

namespace App\Modules\Auth\Services;

use App\Http\Responses\ApiResponse;
use App\Modules\Auth\Support\AuthSupport;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class  AuthService
{
    protected $user;

    private function __construct(User $user)
    {
        $this->user = $user;
    }

    public function signIn($request)
    {
        try {
            $authSupport = new AuthSupport();
            $user = $this->user->getUser($request->username);
            if (!$user || !Hash::check($request->password, $user->password)) return ApiResponse::error('', 'Credenciales incorrectas');
            if ($user->is_enabled == 0) return ApiResponse::error('', 'Usuario inactivo');
            return ApiResponse::success($authSupport->userState($user));
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Error al iniciar sesión', 500);
        }
    }
}
