<?php

namespace Hoang79\PasskeyAuth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryEmailOtp extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'otp'];
}
