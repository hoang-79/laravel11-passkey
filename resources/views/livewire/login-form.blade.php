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
            @if ($errorMessage)
                <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
            @endif
        </form>
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
            @if ($errorMessage)
                <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
            @endif
        </form>
    @elseif ($stage == 'webauthn')
        <p class="mt-2 text-sm text-white-600">Register your Device</p>
    @elseif ($stage == 'register')
        <p>{{ __('Email does not exist. Would you like to register?') }}</p>
        <div class="flex items-center justify-center mt-4">
            <button wire:click="register" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Yes') }}
            </button>
            <button wire:click="$set('stage', 'passkey')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ms-2">
                {{ __('No') }}
            </button>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('webauthnRegister', function (data) {
            const optionsArray = data[0];
            const challenge = data[0].challenge;
            const userId = data[0].user.id
            const sessionId = data[1];

            const options = optionsArray;
            const publicKeyOptions = {
                ...options,
                challenge: Uint8Array.from(atob(challenge.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0)),
                user: {
                    ...options.user,
                    id: Uint8Array.from(atob(userId.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0)),
                },
            };

            navigator.credentials.create({ publicKey: publicKeyOptions })
                .then(credential => {
                    const credentialData = {
                        id: credential.id,
                        rawId: arrayBufferToBase64(credential.rawId),
                        type: credential.type,
                        response: {
                            attestationObject: arrayBufferToBase64(credential.response.attestationObject),
                            clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
                        },
                        authenticatorAttachment: credential.authenticatorAttachment
                    };

                    fetch('/webauthn-register-response', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({credentialData, sessionId})
                    }).then(response => {
                        if (response.ok) {
                            console.log('WebAuthn registration successful');
                            window.location = '/dashboard';
                        } else {
                            console.error('WebAuthn registration failed');
                            @this.setError('Registration failed');
                        }
                    }).catch(error => {
                        console.error('Error during fetch', error);
                    });
                })
                .catch(error => {
                    console.error('Error creating credential', error);
                });
        });

        // Handler for login
        Livewire.on('webauthnLogin', function (data) {
            const challenge = data[0].options.challenge;
            const sessionId = data[0].sessionId;
            const optionsArray = data[0].options;
            const publicKeyOptions = {
                ...optionsArray,
                challenge: Uint8Array.from(atob(challenge.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0)),
                allowCredentials: optionsArray.allowCredentials.map(cred => {
                    if (cred.id) {
                        return {
                            ...cred,
                            id: Uint8Array.from(atob(cred.id.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0)),
                        };
                    } else {
                        return cred;
                    }
                }),
            };

            navigator.credentials.get({ publicKey: publicKeyOptions })
                .then(assertion => {
                    const assertionData = {
                        id: assertion.id,
                        rawId: arrayBufferToBase64(assertion.rawId),
                        type: assertion.type,
                        response: {
                            authenticatorData: arrayBufferToBase64(assertion.response.authenticatorData),
                            clientDataJSON: arrayBufferToBase64(assertion.response.clientDataJSON),
                            signature: arrayBufferToBase64(assertion.response.signature),
                            userHandle: assertion.response.userHandle ? arrayBufferToBase64(assertion.response.userHandle) : null,
                        }
                    };

                    fetch('/webauthn-authenticate-response', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ assertionData, sessionId })
                    }).then(response => {
                        if (response.ok) {
                            window.location = '/dashboard';
                        } else {
                            @this.setError('Login failed');
                        }
                    }).catch(error => {
                    });
                })
                .catch(error => {
                    console.error('Error during assertion', error);
                });
        });

        const otpInputs = document.querySelectorAll('.otp-input');

        otpInputs.forEach((input, index) => {
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && input.value === '') {
                    if (index > 0) {
                        otpInputs[index - 1].focus();
                    }
                }
            });

            input.addEventListener('input', (event) => {
                if (input.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                checkOtpComplete();
            });

            input.addEventListener('paste', (event) => {
                event.preventDefault();
                const pasteData = event.clipboardData.getData('text');
                if (pasteData.length === 6) {
                    otpInputs.forEach((input, index) => {
                        input.value = pasteData[index] || '';
                    });
                    checkOtpComplete();
                }
            });
        });

        function checkOtpComplete() {
            const otpValues = Array.from(otpInputs).map(input => input.value).join('');
            if (otpValues.length === 6) {
                document.getElementById('submit-button').click();
            }
        }
    });

    function arrayBufferToBase64(buffer) {
        let binary = '';
        let bytes = new Uint8Array(buffer);
        let len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

</script>
