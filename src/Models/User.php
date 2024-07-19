<?php

namespace Hoang\PasskeyAuth\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Hoang\PasskeyAuth\Models\Credential;
use Webauthn\PublicKeyCredentialSource;
use Hoang\PasskeyAuth\Models\Authenticator;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class);
    }

    public function addCredential($publicKeyCredentialSource)
    {
        return $this->credentials()->create([
            'credential_id' => $publicKeyCredentialSource->getPublicKeyCredentialId(),
            'type' => $publicKeyCredentialSource->getType(),
            'transports' => $publicKeyCredentialSource->getTransports(),
            'attestation_type' => $publicKeyCredentialSource->getAttestationType(),
            'trust_path' => $publicKeyCredentialSource->getTrustPath(),
            'aaguid' => $publicKeyCredentialSource->getAaguid(),
            'credential_public_key' => $publicKeyCredentialSource->getCredentialPublicKey(),
            'user_handle' => $publicKeyCredentialSource->getUserHandle(),
            'counter' => $publicKeyCredentialSource->getCounter(),
        ]);
    }


    public function authenticators()
    {
        return $this->hasMany(Authenticator::class);
    }


}
