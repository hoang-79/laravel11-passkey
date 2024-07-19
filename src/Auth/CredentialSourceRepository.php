<?php

namespace Hoang\PasskeyAuth\Auth;

use Hoang\PasskeyAuth\Models\Authenticator;
use Hoang\PasskeyAuth\Models\User;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;


class CredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $authenticator = Authenticator::where(
            'credential_id',
            base64_encode($publicKeyCredentialId)
        )->first();

        if (!$authenticator) {
            return null;
        }

        return PublicKeyCredentialSource::createFromArray($authenticator->public_key);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return User::with('authenticators')
            ->where('id', base64_decode($publicKeyCredentialUserEntity->getId()))
            ->first()
            ->authenticators
            ->toArray();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        try {
            $userHandle = base64_decode($publicKeyCredentialSource->getUserHandle());
            $user = User::findOrFail($userHandle);

            $user->authenticators()->save(new Authenticator([
                'credential_id' => $publicKeyCredentialSource->getPublicKeyCredentialId(),
                'public_key' => $publicKeyCredentialSource->jsonSerialize(),
            ]));
        } catch (\Exception $e) {
            dump("Fehler bei der Verarbeitung des Benutzerhandgriffs: " . $e->getMessage());
            throw $e; // Exception weiterwerfen, um den Fehlerprozess zu beenden
        }
    }


}
