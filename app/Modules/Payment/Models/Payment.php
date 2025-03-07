<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'sequence_number',
        'payment_type_id',
        'enrollment_type',
        'enrollment_id',
        'amount',
        'date',
        'is_used',
        'is_enabled',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public static function registerItem($data)
    {
        $item =  self::create([
            'student_id' => $data['studentId'],
            'sequence_number' => $data['sequenceNumber'],
            'payment_type_id' => $data['paymentTypeId'],
            'amount' => $data['amount'],
            'date' => $data['date'],
            'is_used' => false,
            'is_enabled' => true,
        ]);

        return $item;
    }

    public static function markAsUsed($data)
    {
        $item =  self::find($data['id']);
        $item->update([
            'is_used' => true,
        ]);

        return $item;
    }

    public static function markAsUnused($data)
    {
        $item =  self::find($data['id']);
        $item->update([
            'is_used' => false,
        ]);

        return $item;
    }

    public static function sumPayments($data)
    {
        $paymentsIds = array_map(function ($payment) {
            return Crypt::decrypt($payment);
        }, $data['payments']);

        $totalPayment = self::whereIn('id', array_unique($paymentsIds))
            ->where('student_id', $data['studentId'])
            ->where('is_used', false)
            ->sum('amount');

        return $totalPayment;
    }
}
