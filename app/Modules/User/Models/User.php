<?php

namespace App\Modules\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use  Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'model_type',
        'email_verified_at',
        'is_enabled',
        'model_id',
    ];

    protected $hidden = [
        'model_type',
        'model_id',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }


    public static function getUserByEmail($email)
    {
        return self::where('email', $email)
            ->where('model_type', 'student')
            ->first();
    }
    public function getUser($username)
    {
        return self::select('users.*',)
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->where('users.model_type', 'student')
            ->first();
    }

    public static function updatePasswordByEmail($email, $password)
    {
        return self::where('email', $email)->update(['password' => Hash::make($password)]);
    }

    public static function registerItem($person, $studentId, $password)
    {
        $item =  self::create([
            'name' => $person['name'] . ' ' . $person['last_name_father'] . ' ' . $person['last_name_mother'],
            'username' => $person['document_number'],
            'email' => $person['email'],
            'password' => $password,
            'model_type' => 'student',
            'email_verified_at' => now(),
            'model_id' => $studentId,
        ]);

        return $item;
    }
}
