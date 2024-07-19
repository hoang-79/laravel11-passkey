<?php

use Illuminate\Support\Facades\Route;
use Hoang79\PasskeyAuth\Http\Controllers\AuthController;

Route::middleware(['web'])->group(function () {
    Route::get('/passkey', [AuthController::class, 'showLoginForm'])->name('passkey');
    Route::post('/passkey', [AuthController::class, 'login']);
    Route::post('/custom-register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    //Route::post('/webauthn-register', [AuthController::class, 'webauthnRegister']);
    Route::post('/webauthn-register-response', [AuthController::class, 'webauthnRegisterResponse']);
    //Route::post('/webauthn-authenticate', [AuthController::class, 'webauthnAuthenticate']);
    Route::post('/webauthn-authenticate-response', [AuthController::class, 'webauthnAuthenticateResponse']);
});

