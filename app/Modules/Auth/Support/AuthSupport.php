<?php

namespace App\Modules\Auth\Support;

use App\Http\Responses\ApiResponse;
use App\Models\Role;
use App\Models\Person;
use App\Modules\User\Models\User;

class AuthSupport
{

  public static function generateCodeVerification()
  {
    $code = rand(100000, 999999);
    return ['plain' => $code, 'encrypted' => encrypt($code)];
  }

  public static function generatePassword()
  {
    return rand(10000000, 99999999);
  }

  public function userState(User $user)
  {

    try {
      $role = $this->getUserRole($user);
      $currentUser = User::find($user->id);

      return [
        'token' => $this->getUserToken($currentUser),
        'user' => [
          'name' => $user->name,
          'email' => $user->email,
          'role' => $role->name,
          'redirectTo' => $role->redirect_to ? $role->redirect_to : '/',
        ],
        'permissions' => implode('|', $user->getAllPermissions()->pluck('name')->toArray()),
      ];
    } catch (\Exception $e) {
      throw new \Exception("Ocurrió un error al obtener el estado del usuario");
    }
  }

  private function getUserToken(User $user)
  {
    $token = request()->bearerToken();

    if ($token) return $token;

    return $user->createToken('student-access-token')->plainTextToken;
  }

  private function getUserRole(User $user)
  {
    $role = Role::getRole($user->getRoleNames()[0]);
    if (!$role) {
      throw new \Exception('El usuario no tiene un rol asignado');
    }
    return $role;
  }
}
