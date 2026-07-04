<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter your account email and we will send a 6-digit OTP code.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email', [], false) }}" x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Account Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button x-bind:disabled="submitting">
                <span x-show="! submitting">{{ __('Send Reset Code') }}</span>
                <span x-cloak x-show="submitting">{{ __('Sending...') }}</span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
