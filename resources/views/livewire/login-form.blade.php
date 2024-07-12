<div>
        @if($stage == 'passkey')
                <input type="email" wire:model="email" placeholder="Email">
                <button wire:click="submit">Login</button>
        @elseif($stage == 'otp')
                <input type="text" wire:model="otp" placeholder="OTP">
                <button wire:click="submit">Verify OTP</button>
        @elseif($stage == 'webauthn')
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
        @elseif($stage == 'custom-register')
                <p>Register your device</p>
        @endif
</div>
