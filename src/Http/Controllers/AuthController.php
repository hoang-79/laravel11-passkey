<?php

namespace Hoang79\PasskeyAuth\Http\Controllers;

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
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Hoang79\PasskeyAuth\Models\TemporaryEmailOtp;
use Hoang79\PasskeyAuth\Mail\SendOtpMail;
use Hoang79\PasskeyAuth\Models\User;
use CBOR\Decoder;
use CBOR\StringStream;
use Webauthn\CollectedClientData;
use Webauthn\AttestationObject;
use Hoang79\PasskeyAuth\Auth\CredentialSourceRepository;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use App\Models\Team;
use Webauthn\PublicKeyCredentialSource;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredential;


class AuthController extends Controller
{
    const CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY = 'publicKeyCredentialRequestOptions'; // Add this line

    protected $serializer;

    public $errorMessage = '';

    public function __construct()
    {
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    }

    /**
     * Zeigt das Login-Formular an.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('passkeyauth::login');
    }

    /**
     * Handhabt den Login-Prozess.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validierung der Anfrage
            $request->validate(['email' => 'required|email']);

            // Benutzer suchen
            $user = User::where('email', $request->email)->first();

            if ($user) {
                $options = $this->generateWebAuthnAuthenticateOptions($user);
                // Session-ID an den Client senden
                $sessionId = session()->getId();
                return response()->json([
                    'webauthnLogin' => true,
                    'message' => 'WebAuthn authentication required',
                    'options' => $options,
                    'sessionId' => $sessionId
                ]);
            }

            return response()->json(['webauthn' => false, 'message' => 'Email does not exist'], 404);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred during login'], 500);
        }
    }

    protected function generateWebAuthnAuthenticateOptions($user)
    {
        // Ensure the user ID is correctly encoded
        $userId = base64_encode((string) $user->id);

        // Create the User Entity
        $userEntity = new PublicKeyCredentialUserEntity($user->email, $userId, $user->email, null);

        $pkSourceRepo = new CredentialSourceRepository();

        // Retrieve all registered authenticators for the user
        $registeredAuthenticators = $pkSourceRepo->findAllForUserEntity($userEntity);

        $allowedCredentials = collect($registeredAuthenticators)
            ->map(function ($authenticator) {
                try {
                    $credentialSource = PublicKeyCredentialSource::createFromArray($authenticator['public_key']);
                    return $credentialSource;
                } catch (\Exception $e) {
                    return null;
                }
            })
            ->filter()
            ->map(function (PublicKeyCredentialSource $credential) {
                return $credential->getPublicKeyCredentialDescriptor();
            })
            ->toArray();

        $pkRequestOptions = PublicKeyCredentialRequestOptions::create(
            random_bytes(32)
        )->allowCredentials(...$allowedCredentials);

        $serializedOptions = $pkRequestOptions->jsonSerialize();
        $serializedOptions['user']['id'] = $userId; // Add user ID here
        session()->put(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY, $serializedOptions);

        return $serializedOptions;
    }

    /**
     * Bereitet die WebAuthn-Authentifizierung vor.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public function webauthnAuthenticateResponse(Request $request, ServerRequestInterface $serverRequest)
    {
        try {
            // Retrieve session ID
            $sessionId = $request->input('sessionId');
            session()->setId($sessionId);
            session()->start();

            // Create CredentialSourceRepository
            $pkSourceRepo = new CredentialSourceRepository();

            // Create AttestationStatementSupportManager
            $attestationManager = AttestationStatementSupportManager::create();
            $attestationManager->add(NoneAttestationStatementSupport::create());

            // Create AlgorithmManager
            $algorithmManager = Manager::create()->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),
                RS256::create(),
                RS384::create(),
                RS512::create(),
                PS256::create(),
                PS384::create(),
                PS512::create(),
                Ed256::create(),
                Ed512::create(),
            );

            // Create AuthenticatorAssertionResponseValidator
            $responseValidator = AuthenticatorAssertionResponseValidator::create(
                $pkSourceRepo,
                IgnoreTokenBindingHandler::create(),
                ExtensionOutputCheckerHandler::create(),
                $algorithmManager,
            );

            // Create WebauthnSerializerFactory with AttestationStatementSupportManager
            $serializerFactory = new WebauthnSerializerFactory($attestationManager);
            $serializer = $serializerFactory->create();

            $assertionData = $request->input('assertionData');

            // Adjust the data to remove padding and replace URL-safe Base64 characters
            $this->processBase64Encoding($assertionData);

            // Load assertion data
            $jsonAssertionData = json_encode($assertionData);

            // Deserialize assertion data
            $publicKeyCredential = $serializer->deserialize($jsonAssertionData, PublicKeyCredential::class, 'json');
            $authenticatorAssertionResponse = $publicKeyCredential->getResponse();

            if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
                throw ValidationException::withMessages([
                    'username' => 'Invalid response type',
                ]);
            }

            // Retrieve stored options
            $optionsSerialized = session(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY);

            $options = $optionsSerialized; // Use the array directly

            // Ensure allowCredentials is an array of arrays
            $options['allowCredentials'] = array_map(function ($credential) {
                return $credential->jsonSerialize();
            }, $options['allowCredentials']);

            // Validate the response
            $publicKeyCredentialSource = $responseValidator->check(
                $publicKeyCredential->getRawId(),
                $authenticatorAssertionResponse,
                PublicKeyCredentialRequestOptions::createFromArray($options),
                $serverRequest,
                $authenticatorAssertionResponse->getUserHandle(),
            );

            // Remove stored options
            $request->session()->forget(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY);

            $getUserHandle = base64_decode($publicKeyCredentialSource->getUserHandle());

            // Authenticate the user
            $user = User::findOrFail($getUserHandle);

            Auth::login($user);

            return response()->json(['message' => 'Authentication successful']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred during authentication', 'error' => $e->getMessage()], 500);
        }
    }









    /**
     * Handhabt die Registrierung eines neuen Benutzers.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);
        TemporaryEmailOtp::updateOrCreate(
            ['email' => $request->email], // Condition to find the existing entry
            ['otp' => $otp] // Data to update or create
        );

        dump("OTP erstellt: " . $otp);

        try {
            Mail::to($request->email)->send(new SendOtpMail($otp));
            $this->setError("OTP-E-Mail gesendet");
        } catch (\Exception $e) {
            $this->setError("Failed to send OTP email. Please try again later.");
            return response()->json(['message' => 'Failed to send OTP email. Please try again later.'], 500);
        }

        return response()->json(['message' => 'OTP sent to email']);
    }

    /**
     * Verifiziert das eingegebene OTP.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                'password' => Hash::make('password') // Default password
            ]);


            Auth::login($user);

            $this->createTeam($user);

            // OTP nach erfolgreicher Verifizierung löschen
            $temporaryOtp->delete();

            $options = $this->generateWebAuthnRegisterOptions($user);

            $this->setError("Account created successfully");

            // Session-ID an den Client senden
            $sessionId = session()->getId();

            $response = response()->json([
                'message' => 'Account created successfully',
                'options' => $options,
                'webauthnRegister' => true,
                'sessionId' => $sessionId
            ]);

            return $response;
        }

        return response()->json(['message' => 'Invalid OTP'], 422);
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }

    protected function generateWebAuthnRegisterOptions($user)
    {
        $rpEntity = new PublicKeyCredentialRpEntity(config('app.name'), config('app.url', 'localhost'));
        $userEntity = new PublicKeyCredentialUserEntity($user->email, base64_encode($user->id), $user->name);

        $authenticatorSelection = new AuthenticatorSelectionCriteria();
        $challenge = strtr(base64_encode(random_bytes(32)), '+/', '-_'); // URL-safe Base64 encoding

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

        return $options;
    }

    /**
     * Verarbeitet die Antwort der WebAuthn-Registrierung.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webauthnRegisterResponse(Request $request, ServerRequestInterface $serverRequest)
    {
        try {
            // Retrieve session ID
            $sessionId = $request->input('sessionId');

            session()->setId($sessionId);
            session()->start();

            // Create CredentialSourceRepository
            $pkSourceRepo = new CredentialSourceRepository();

            // Create AttestationStatementSupportManager
            $attestationManager = AttestationStatementSupportManager::create();
            $attestationManager->add(NoneAttestationStatementSupport::create());

            $responseValidator = AuthenticatorAttestationResponseValidator::create(
                $attestationManager,
                $pkSourceRepo,
                IgnoreTokenBindingHandler::create(),
                ExtensionOutputCheckerHandler::create(),
            );

            // Create WebauthnSerializerFactory with AttestationStatementSupportManager
            $serializerFactory = new WebauthnSerializerFactory($attestationManager);

            $serializer = $serializerFactory->create();

            $credentialData = $request->input('credentialData');

            // Adjust the data to remove padding and replace URL-safe Base64 characters
            $this->processBase64Encoding($credentialData);

            // JSON-Encoding der Daten
            $jsonCredentialData = json_encode($credentialData);

            // Vor dem Laden des PublicKeyCredentials
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON encoding error: ' . json_last_error_msg());
            }

            // Deserialize credential data
            $publicKeyCredential = $serializer->deserialize($jsonCredentialData, PublicKeyCredential::class, 'json');

            $authenticatorAttestationResponse = $publicKeyCredential->getResponse();

            if (!$authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
                throw ValidationException::withMessages([
                    'username' => 'Invalid response type',
                ]);
            }

            // Retrieve stored options
            $optionsSerialized = session('webauthn.register');

            // Ensure the options are in JSON format
            $optionsSerialized = unserialize($optionsSerialized);

            // Debugging the JSON string before deserialization

            // JSON-Encoding der Daten
            $jsonOptionsSerialized = json_encode($optionsSerialized);


            // Deserialize the stored options
            $options = $serializer->deserialize($jsonOptionsSerialized, PublicKeyCredentialCreationOptions::class, 'json');

            // Validate the response
            $publicKeyCredentialSource = $responseValidator->check(
                $authenticatorAttestationResponse,
                $options,
                $serverRequest
            );

            $pkSourceRepo->saveCredentialSource($publicKeyCredentialSource);

            return response()->json(['message' => 'Registration successful']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred during registration', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Setzt eine Fehlermeldung.
     *
     * @param string $message
     * @return void
     */
    public function setError($message)
    {
        $this->errorMessage = $message;
    }


    protected function processBase64Encoding(&$data)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->processBase64Encoding($value);
            } else {
                // Entfernen von '=' Zeichen
                $value = str_replace('=', '', $value);
                // Ersetzen von URL-sicheren Base64-Zeichen durch Standard Base64-Zeichen
                //$value = str_replace(['-', '_'], ['+', '/'], $value);
                // Polsterung hinzufügen, um sicherzustellen, dass die Länge durch 4 teilbar ist
                $value = str_pad($value, strlen($value) % 4, '=', STR_PAD_RIGHT);
            }
        }
    }

}
