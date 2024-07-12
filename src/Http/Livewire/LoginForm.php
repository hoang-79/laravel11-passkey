<?php

namespace Hoang\PasskeyAuth\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class LoginForm extends Component
{
    public $email = '';
    public $otp = '';
    public $stage = 'passkey';
    public $webauthnOptions;

    public function submit()
    {
        if ($this->stage == 'passkey') {
            $response = Http::post('/passkey', ['email' => $this->email]);
            dump("Response: " . $response);
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
                $this->stage = 'custom-register';
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
