<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_and_login_with_webauthn()
    {
        // Simulate registration
        $user = User::factory()->create(['email' => 'test@example.com']);

        Auth::login($user);

        // WebAuthn registration
        $registerResponse = $this->post('/webauthn-register');
        $registerResponse->assertStatus(200);

        // WebAuthn registration response
        $credential = $this->mockWebAuthnCredential($registerResponse->json());
        $registerResponse = $this->post('/webauthn-register-response', ['credential' => $credential]);
        $registerResponse->assertStatus(200);

        // Logout
        Auth::logout();

        // WebAuthn authentication
        Auth::login($user);
        $authenticateResponse = $this->post('/webauthn-authenticate');
        $authenticateResponse->assertStatus(200);

        // WebAuthn authentication response
        $credential = $this->mockWebAuthnCredential($authenticateResponse->json());
        $authenticateResponse = $this->post('/webauthn-authenticate-response', ['credential' => $credential]);
        $authenticateResponse->assertStatus(200);
    }

    protected function mockWebAuthnCredential($options)
    {
        // Mock WebAuthn credential creation logic here
        return [
            'id' => 'mocked-credential-id',
            'rawId' => 'mocked-raw-id',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => 'mocked-client-data-json',
                'attestationObject' => 'mocked-attestation-object',
            ],
        ];
    }
}
