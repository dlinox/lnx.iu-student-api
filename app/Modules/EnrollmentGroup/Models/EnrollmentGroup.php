<?php

namespace App\Modules\EnrollmentGroup\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Model;

class EnrollmentGroup extends Model
{
    use HasDataTable;

    protected $fillable = [
        'student_id',
        'group_id',
        'period_id',
        'payment_id',
    ];
}
