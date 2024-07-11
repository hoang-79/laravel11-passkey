<?php

namespace Hoang\PasskeyAuth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id',
        'type',
        'transports',
        'attestation_type',
        'trust_path',
        'aaguid',
        'credential_public_key',
        'user_handle',
        'counter'
    ];

    protected $casts = [
        'transports' => 'array',
        'trust_path' => 'array',
        'aaguid' => 'string',
        'credential_public_key' => 'string',
        'user_handle' => 'string',
        'counter' => 'integer',
    ];
}
