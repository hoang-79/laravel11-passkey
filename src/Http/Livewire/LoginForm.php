<?php

namespace Hoang\PasskeyAuth\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class LoginForm extends Component
{
    public $email = '';
    public $otp = '';
    public $stage = 'login';

    public function submit()
    {
        if ($this->stage == 'login') {
            // Check if the email exists and trigger WebAuthn

            $this->stage = 'otp';
        } elseif ($this->stage == 'otp') {
            // Verify OTP

            $this->stage = 'register';
        }
    }

    public function render()
    {
        return view('passkeyauth::livewire.login-form');
    }
}
