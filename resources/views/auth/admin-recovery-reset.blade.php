<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Choose a new password. This administrative recovery link can only be used once.') }}
    </div>

    <form method="POST" action="{{ route('admin-recovery.reset.store', ['recovery' => $recovery, 'token' => $token], false) }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>{{ __('Reset Password') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
