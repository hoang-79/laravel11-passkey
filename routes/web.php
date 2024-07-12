<?php

use Illuminate\Support\Facades\Route;
use Hoang\PasskeyAuth\Http\Controllers\AuthController;

Route::middleware(['web'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/custom-register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/webauthn-register', [AuthController::class, 'webauthnRegister']);
    Route::post('/webauthn-register-response', [AuthController::class, 'webauthnRegisterResponse']);
    Route::post('/webauthn-authenticate', [AuthController::class, 'webauthnAuthenticate']);
    Route::post('/webauthn-authenticate-response', [AuthController::class, 'webauthnAuthenticateResponse']);
});
