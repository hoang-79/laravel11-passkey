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
            $response = Http::post(url('/passkey'), ['email' => $this->email]);

            if ($response->status() == 200) {
                if ($response->json()['webauthn']) {
                    $this->webauthnOptions = $response->json();
                    $this->stage = 'webauthn';
                } else {
                    $this->stage = 'otp';
                }
            } else {
                $this->stage = 'register';
            }
        } elseif ($this->stage == 'otp') {
            $response = Http::post(url('/verify-otp'), [
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

    public function register()
    {
        $response = Http::post(url('/custom-register'), ['email' => $this->email]);

        if ($response->status() == 200) {
            $this->stage = 'otp';
        } else {
            session()->flash('error', $response->json()['message'] ?? 'Unknown error');
        }
    }

    public function render()
    {
        return view('passkeyauth::livewire.login-form');
    }
}
