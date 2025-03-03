<?php

namespace App\Modules\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use  Notifiable,  HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'account_level',
        'email_verified_at',
        'is_enabled',
        'model_id',
    ];

    protected $hidden = [
        'account_level',
        'model_id',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
