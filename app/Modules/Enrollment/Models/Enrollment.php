<?php

namespace App\Modules\Enrollment\Models;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'curriculum_id',
        'student_id',
        'module_id',
        'payment_id',
    ];
}
