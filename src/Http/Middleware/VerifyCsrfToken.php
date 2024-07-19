<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'passkey',
        'custom-register',
        'verify-otp',
        'webauthn-register',
        'webauthn-register-response',
        'webauthn-authenticate',
        'webauthn-authenticate-response',
    ];
}
