<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->group(function () {
    Route::post('/sign-in', [AuthController::class, 'signIn'])->middleware(['throttle:singIn']);
    Route::post('/sign-out', [AuthController::class, 'signOut'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/register-student-type', [AuthController::class, 'registerStudentType'])->middleware(['throttle:registerStudentType']);
    Route::post('/register-student', [AuthController::class, 'registerNewStudent']);
    Route::post('/send-create-account', [AuthController::class, 'sendCreateAccount']);
    Route::post('/create-account', [AuthController::class, 'createAccount']);
    Route::get('/get-basic-information-student/{payload}', [AuthController::class, 'getBasicInformationStudent']);
    Route::get('/get-student-by-type/{payload}', [AuthController::class, 'getStudentByType']);
});
