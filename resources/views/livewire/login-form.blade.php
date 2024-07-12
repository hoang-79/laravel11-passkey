<div>
    @if ($stage == 'passkey')
        <form wire:submit.prevent="submit">
            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" wire:model="email" required autofocus />
            </div>
            <div class="flex items-center justify-end mt-4">
                <x-button class="ms-4">
                    {{ __('Login') }}
                </x-button>
            </div>
        </form>
    @elseif ($stage == 'register')
        <p>{{ __('Email does not exist. Do you want to create an account?') }}</p>
        <div class="flex items-center justify-end mt-4">
            <x-button class="ms-4" wire:click="register">
                {{ __('Yes') }}
            </x-button>
            <x-button class="ms-4" wire:click="$set('stage', 'passkey')">
                {{ __('No') }}
            </x-button>
        </div>
    @elseif ($stage == 'otp')
        <form wire:submit.prevent="submit">
            <div>
                <x-label for="otp" value="{{ __('OTP') }}" />
                <x-input id="otp" class="block mt-1 w-full" type="text" wire:model="otp" required />
            </div>
            <div class="flex items-center justify-end mt-4">
                <x-button class="ms-4">
                    {{ __('Verify OTP') }}
                </x-button>
            </div>
        </form>
    @elseif ($stage == 'webauthn')
        <script>
            navigator.credentials.create({publicKey: @json($webauthnOptions)})
                .then(credential => {
                    fetch('/webauthn-register-response', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({credential})
                    }).then(response => {
                        if (response.ok) {
                            window.location = '/dashboard';
                        } else {
                            alert('Registration failed');
                        }
                    });
                })
                .catch(error => {
                    console.error('Error creating credential', error);
                });
        </script>
    @elseif ($stage == 'custom-register')
        <p>{{ __('Register your device') }}</p>
    @endif
</div>
