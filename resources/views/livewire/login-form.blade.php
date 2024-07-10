<div>
    @if($stage == 'login')
        <input type="email" wire:model="email" placeholder="Email">
        <button wire:click="submit">Login</button>
    @elseif($stage == 'otp')
        <input type="text" wire:model="otp" placeholder="OTP">
        <button wire:click="submit">Verify OTP</button>
    @elseif($stage == 'register')
        <p>Register your device</p>
        <!-- WebAuthn registration logic -->
    @endif
</div>
