<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Complete CAPTCHA verification to receive a one-time password at your registered email address.') }}
    </div>

    <form method="POST" action="{{ route('account.unlock.store', [], false) }}">
        @csrf

        <div>
            <x-input-label for="captcha" :value="__('CAPTCHA: What is '.$captchaQuestion.'?')" />
            <x-text-input id="captcha" class="block mt-1 w-full" type="text" name="captcha" required inputmode="numeric" autocomplete="off" autofocus />
            <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>{{ __('Send OTP') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
