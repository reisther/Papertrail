<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Complete CAPTCHA verification to receive a one-time password at your registered email address.') }}
    </div>

    <form method="POST" action="{{ route('account.unlock.store', [], false) }}">
        @csrf

        <div class="flex justify-center">
            <x-recaptcha />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>{{ __('Send OTP') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
