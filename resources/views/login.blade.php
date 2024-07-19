<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif


        {{--<style>
            .otp-input {
                width: 2.5rem; /* Width to accommodate one character */
                height: 2.5rem; /* Adjust height to match the width */
                border: 1px solid #d1d5db; /* Match the email field's border */
                text-align: center; /* Center text */
                margin-right: 0.25rem; /* Small margin to separate the fields */
                border-radius: 0.375rem; /* Border radius similar to email field */
                font-size: 1.25rem; /* Font size similar to email field */
                color: white;
            }
            .otp-input:focus {
                outline: none;
                border-color: #3b82f6; /* Match the focus color of the email field */
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); /* Match the focus shadow of the email field */
            }
        </style>--}}

        <livewire:login-form />
    </x-authentication-card>
</x-guest-layout>
