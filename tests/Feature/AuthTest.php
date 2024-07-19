<?php

namespace Tests\Feature;

use Hoang\PasskeyAuth\Models\TemporaryEmailOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register_webauthn_key()
    {
        $user = User::factory()->create();

        Auth::login($user);

        $response = $this->post('/webauthn-register');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'rp',
            'user',
            'challenge',
            'pubKeyCredParams',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_authenticate_with_webauthn_key()
    {
        $user = User::factory()->create();

        Auth::login($user);

        $response = $this->post('/webauthn-authenticate');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'challenge',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function otp_is_sent_when_registering()
    {
        $response = $this->post('/custom-register', ['email' => 'test@example.com']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('temporary_email_otps', ['email' => 'test@example.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function otp_verification_fails_with_invalid_otp()
    {
        TemporaryEmailOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '654321',
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function otp_verification_succeeds_with_valid_otp()
    {
        TemporaryEmailOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('temporary_email_otps', ['email' => 'test@example.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/passkey', ['email' => 'test@example.com']);

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_login_with_invalid_email()
    {
        $response = $this->post('/passkey', ['email' => 'invalid@example.com']);

        $response->assertStatus(404);
    }
}
