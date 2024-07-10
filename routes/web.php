<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/login', 'Hoang\PasskeyAuth\Http\Controllers\AuthController@showLoginForm')->name('login');
    Route::post('/login', 'Hoang\PasskeyAuth\Http\Controllers\AuthController@login');
    Route::post('/register', 'Hoang\PasskeyAuth\Http\Controllers\AuthController@register');
    Route::post('/verify-otp', 'Hoang\PasskeyAuth\Http\Controllers\AuthController@verifyOtp');
});
