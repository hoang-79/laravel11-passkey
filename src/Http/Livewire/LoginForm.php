<?php
namespace Hoang\PasskeyAuth\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoginForm extends Component
{
    public $email = '';
    public $otp = '';
    public $stage = 'passkey';
    public $webauthnOptions;
    public $errorMessage;

    public function submit()
    {
        if ($this->stage == 'passkey') {
            $response = Http::post(url('/passkey'), ['email' => $this->email]);

            if ($response->status() == 200) {
                $this->webauthnOptions = $response->json();
                $this->stage = 'webauthnLogin';
                $this->dispatch('webauthnLogin', $this->webauthnOptions);
            } elseif ($response->status() === 404) {
                $this->stage = 'register';
            } else {
                $this->setError($response->json()['message'] ?? 'Unknown error');
            }
        } elseif ($this->stage == 'otp') {
            /*$this->otp = collect(range(0, 5))->map(function ($i) {
                return $this->{'otp' . $i};
            })->join('');*/

            $response = Http::post(url('/verify-otp'), [
                'email' => $this->email,
                'otp' => $this->otp,
            ]);

            if ($response->status() == 200) {
                $data = $response->json();
                $this->webauthnOptions = $data['options'];
                $sessionId = $data['sessionId'];
                $this->stage = 'webauthnRegister';
                $this->dispatch('webauthnRegister', $this->webauthnOptions, $sessionId);
            } else {
                $this->setError($response->json()['message'] ?? 'Unknown error');
            }
        }
    }

    public function completeWebauthnRegistration()
    {
        // This function will be called from the JavaScript when the WebAuthn registration is complete
        $this->redirect('/dashboard');
    }


    public function register()
    {
        $this->makeRequest(url('/custom-register'), ['email' => $this->email], 'otp');
    }

    public function makeRequest($url, $data, $stage)
    {
        $data['_token'] = csrf_token();
        Log::info('LoginForm::makeRequest - url: ' . $url . ', data: ' . json_encode($data) . ', stage: ' . $stage);

        $response = Http::post($url, $data);
        Log::info('LoginForm::makeRequest - Response: ' . $response->body());

        if ($response->successful()) {
            $this->stage = $stage;
        } else {
            Log::error('LoginForm::makeRequest - Error: ' . $response->body());
            $this->setError($response->json('message') ?? 'Unknown error');
        }
    }

    public function setError($message)
    {
        $this->errorMessage = $message;
    }

    public function render()
    {
        return view('passkeyauth::livewire.login-form');
    }
}

