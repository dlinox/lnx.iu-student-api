<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'sequence_number',
        'payment_type_id',
        'amount',
        'date',
        'is_enabled',
    ];
}
