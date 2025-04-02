<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiStudentTypeService
{
    public function __construct() {}

    public static function getStudentType($studentCode)
    {
        $response = Http::withOptions([
            'verify' => env('API_VERIFY_SSL', false),
        ])->asMultipart()->post('https://intranet.unap.edu.pe/biblioteca/api/get-student-infouna/', [
            ['name' => 'studentCode', 'contents' => $studentCode]
        ]);
        return $response->json();
    }
}
