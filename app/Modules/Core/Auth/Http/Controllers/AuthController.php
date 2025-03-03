<?php

namespace App\Modules\Core\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Core\Auth\Http\Requests\SignUpRequest;
use App\Modules\Core\Person\Models\Person;
use App\Modules\User\Models\User;

use App\Modules\Student\Models\Student;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function signIn(Request $request)
    {
        $user = $this->user->select(
            'users.*',
        )
            ->where(function ($query) use ($request) {
                $query->where('username', $request->username)
                    ->orWhere('email', $request->username);
            })
            ->where('users.account_level', 'student')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('', 'Credenciales incorrectas');
        }

        if ($user->is_enabled == 0) {
            return ApiResponse::error('', 'Usuario inactivo',);
        }

        return ApiResponse::success($this->userState($user));
    }

    public function signUp(SignUpRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $person = Person::registerItem($data);
            $data['person_id'] = $person->id;

            $student = Student::registerItem($data);

            $user = User::create([
                'name' => $person->name . ' ' . $person->last_name_father . ' ' . $person->last_name_mother,
                'username' => $person->document_number,
                'email' => $data['email'],
                'password' => $person->document_number,
                'is_enabled' => true,
                'account_level' => 'student',
                'model_id' => $student->id,
            ]);

            $user->syncRoles(['estudiante']);

            $userState = $this->userState($user);

            DB::commit();
            return ApiResponse::success($userState, 'Registro creado correctamente', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage());
        }
    }


    public function signOut(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success('Sesión cerrada, hasta luego');
    }

    public function user(Request $request)
    {
        $user =  $request->user();
        $userState = $this->userState($user);
        return ApiResponse::success($userState);
    }

    private function userState($user)
    {
        $role = $this->getUserRole($user);
        $currentUser = User::find($user->id);

        return [
            'token' => $this->getUserToken($currentUser),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role->name,
                'redirectTo' => "/",
            ],
            'permissions' => implode('|', $user->getAllPermissions()->pluck('name')->toArray()),
        ];
    }

    private function getUserToken($user)
    {

        $token = request()->bearerToken();

        if ($token) {
            return $token;
        }

        return $user->createToken('admin-access-token')->plainTextToken;
    }

    private function getUserRole($user)
    {
        try {

            $role = Role::where('name', $user->getRoleNames()[0])->first();
            if (!$role) {
                return ApiResponse::error('El usuario no tiene un rol asignado', 401);
            }
            return $role;
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener el rol del usuario');
        }
    }
}
