<?php

namespace Hoang79\PasskeyAuth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;

    protected $table = 'credentials';

    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key'
    ];

    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
