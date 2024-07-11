<?php

namespace Hoang\PasskeyAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAssertionResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Webauthn\AuthenticatorSelectionCriteria;
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

    // Debugging-Methode
    public function debugPublicKeyCredentialCreationOptions(
        PublicKeyCredentialRpEntity $rp,
        PublicKeyCredentialUserEntity $user,
        string $challenge,
        array $pubKeyCredParams,
        ?AuthenticatorSelectionCriteria $authenticatorSelection,
        ?string $attestation,
        array $excludeCredentials,
        ?int $timeout,
        ?AuthenticationExtensions $extensions
    ) {
        dump($rp, $user, $challenge, $pubKeyCredParams, $authenticatorSelection, $attestation, $excludeCredentials, $timeout, $extensions);
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
            return response()->json(['message' => 'WebAuthn authentication not implemented yet']);
        }

        return response()->json(['message' => 'Email does not exist'], 404);
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
            $user = User::create([
                'name' => $request->email,
                'email' => $request->email,
                'password' => Hash::make('password'), // Default password
            ]);

            Auth::login($user);

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

        $authenticatorSelection = new AuthenticatorSelectionCriteria(
            null, // authenticatorAttachment (optional)
            'preferred', // userVerification
            null,
            false // requireResidentKey
        );


        // Rufen Sie die Debugging-Methode auf
        $this->debugPublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            random_bytes(32),
            [
                [
                    'type' => Algorithms::COSE_ALGORITHM_ES256,
                    'alg' => -7,
                ],
            ],
            $authenticatorSelection,
            null,
            ['direct']
        );



        $options = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            random_bytes(32),
            [
                [
                    'type' => \Cose\Algorithms::COSE_ALGORITHM_ES256,
                    'alg' => -7,
                ],
            ],
            60000,
            $authenticatorSelection,
            ['direct']
        );

        session(['webauthn.register' => serialize($options)]);

        return response()->json($options);
    }

    public function webauthnAuthenticate(Request $request)
    {
        $user = Auth::user();

        $rpEntity = new PublicKeyCredentialRpEntity(
            config('app.name'),
            'localhost'
        );

        $options = new PublicKeyCredentialRequestOptions(
            random_bytes(32),
            60000,
            $rpEntity,
            $user->credentialIds,
            ['internal']
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
