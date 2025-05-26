<?php

namespace App\Services;

use App\Models\PaymentType;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function __construct() {}

    public static function validatePaymentBank($request)
    {
         if (config('app.env') === 'local') {
            return true;
        }

        $paymentType  = PaymentType::select('commission')
            ->where('id', $request['paymentTypeId'])
            ->first();

        $amount = $request['amount'] + $paymentType->commission;
        $url = 'https://service2.unap.edu.pe/PayOnBankINFOUNA/v1/bySequence/' . $request['sequenceNumber'] . '/' . $request['date'] . '/' . $amount;

        $response = Http::withOptions([
            'verify' => env('API_VERIFY_SSL', false),
        ])->asMultipart()->get($url);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['status'] === true) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }   
}
