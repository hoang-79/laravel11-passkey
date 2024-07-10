<?php

namespace Hoang\PasskeyAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Hoang\PasskeyAuth\Models\TemporaryEmailOtp;
use Hoang\PasskeyAuth\Mail\SendOtpMail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('passkeyauth::login');
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Here you would implement the WebAuthn authentication logic

        return response()->json(['message' => 'WebAuthn logic not implemented yet']);
    }

    public function register(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);
        TemporaryEmailOtp::create([
            'email' => $request->email,
            'otp' => $otp,
        ]);

        Mail::to($request->email)->send(new SendOtpMail($otp));

        return response()->json(['message' => 'OTP sent to email']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $temporaryOtp = TemporaryEmailOtp::where('email', $request->email)->where('otp', $request->otp)->first();

        if ($temporaryOtp) {
            // Create user account logic

            return response()->json(['message' => 'Account created successfully']);
        }

        return response()->json(['message' => 'Invalid OTP'], 422);
    }
}
