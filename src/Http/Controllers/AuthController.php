<?php

namespace Hoang\PasskeyAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use Cose\Algorithms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAssertionResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Hoang\PasskeyAuth\Models\TemporaryEmailOtp;
use Hoang\PasskeyAuth\Mail\SendOtpMail;
use App\Models\User;

class AuthController extends Controller
{
    protected $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    }

    public function showLoginForm()
    {
        return view('passkeyauth::login');
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            return response()->json(['webauthn' => true, 'message' => 'WebAuthn authentication required']);
        }

        return response()->json(['webauthn' => false, 'message' => 'Email does not exist']);
    }

    public function register(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);
        TemporaryEmailOtp::create([
            'email' => $request->email,
            'otp' => $otp,
        ]);

        try {
            Mail::to($request->email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send OTP email. Please try again later.'], 500);
        }

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
            $user = User::create([
                'name' => $request->email,
                'email' => $request->email,
                'password' => Hash::make('password'), // Default password
            ]);

            Auth::login($user);

            // OTP nach erfolgreicher Verifizierung löschen
            $temporaryOtp->delete();

            return response()->json(['message' => 'Account created successfully']);
        }

        return response()->json(['message' => 'Invalid OTP'], 422);
    }

    public function webauthnRegister(Request $request)
    {
        $user = Auth::user();

        $rpEntity = new PublicKeyCredentialRpEntity(
            config('app.name'),
            'localhost'
        );

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email,
            $user->id,
            $user->name
        );

        $authenticatorSelection = new AuthenticatorSelectionCriteria();

        $challenge = random_bytes(32);

        $publicKeyCredentialParametersList = [
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),
        ];

        $options = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            $challenge,
            $publicKeyCredentialParametersList,
            $authenticatorSelection,
            null,
            [],
            60000,
            new AuthenticationExtensions()
        );

        session(['webauthn.register' => serialize($options)]);

        return response()->json($options);
    }

    public function webauthnAuthenticate(Request $request)
    {
        $user = Auth::user();

        $challenge = random_bytes(32);

        $options = new PublicKeyCredentialRequestOptions(
            $challenge,
            'localhost',
            $user->credentials->pluck('id')->toArray(),
            'preferred',
            60000,
            new AuthenticationExtensions()
        );

        session(['webauthn.authenticate' => serialize($options)]);

        return response()->json($options);
    }

    public function webauthnRegisterResponse(Request $request)
    {
        $user = Auth::user();

        $attestation = $this->serializer->deserialize($request->input('credential'), AuthenticatorAttestationResponse::class, 'json');
        $options = unserialize(session('webauthn.register'));

        $authenticator = new AuthenticatorAttestationResponse();
        $publicKeyCredentialSource = $authenticator->validate($attestation, $options);

        $user->addCredential($publicKeyCredentialSource);

        return response()->json(['message' => 'Registration successful']);
    }

    public function webauthnAuthenticateResponse(Request $request)
    {
        $user = Auth::user();

        $assertion = $this->serializer->deserialize($request->input('credential'), AuthenticatorAssertionResponse::class, 'json');
        $options = unserialize(session('webauthn.authenticate'));

        $authenticator = new AuthenticatorAssertionResponse();
        $authenticator->validate($assertion, $options, $user->credentials);

        return response()->json(['message' => 'Authentication successful']);
    }
}
