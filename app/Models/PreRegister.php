<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreRegister extends Model
{
    protected $fillable = [
        'email',
        'token',
        'student_type',
        'student_code',
        'email_verified',
        'status',
    ];

    //cast
    protected $casts = [
        'email_verified' => 'boolean',
        'status' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //registrar o actualizar pre-registro

    public static function registerItem($data)
    {
        $preRegister = self::where('email', $data['email'])->first();
        if ($preRegister) {
            $preRegister->update($data);
        } else {
            $preRegister = self::create($data);
        }
        return $preRegister;
    }

    public static function findItemByPayload($payload)
    {
        $id = decrypt($payload);
        return self::where('id', $id)->first();
    }
}
