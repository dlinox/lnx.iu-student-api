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
        'created_by',
        'enrollment_modality',
        'special_enrollment',
        'with_enrollment',
        'status',
    ];

    static $searchColumns = [
        'modules.name',
        'courses.name',
        'groups.name',
        'areas.name',
        'periods.year',
    ];
}
