<?php

namespace Hoang\PasskeyAuth\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class LoginForm extends Component
{
    public $email = '';
    public $otp = '';
    public $stage = 'login';
    public $webauthnOptions;

    public function submit()
    {
        if ($this->stage == 'login') {
            $response = Http::post('/login', ['email' => $this->email]);

            if ($response->status() == 200) {
                $this->webauthnOptions = $response->json();
                $this->stage = 'webauthn';
            } else {
                session()->flash('error', $response->json()['message']);
            }
        } elseif ($this->stage == 'otp') {
            $response = Http::post('/verify-otp', [
                'email' => $this->email,
                'otp' => $this->otp,
            ]);

            if ($response->status() == 200) {
                $this->stage = 'register';
            } else {
                session()->flash('error', $response->json()['message']);
            }
        }
    }

    public function render()
    {
        return view('passkeyauth::livewire.login-form');
    }
}
