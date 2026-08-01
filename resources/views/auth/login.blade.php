<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('login_attempt_notice'))
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-900" role="status">
            {{ session('login_attempt_notice') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login', [], false) }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-12"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <button type="button"
                        id="togglePassword"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        aria-label="{{ __('Show password') }}"
                        aria-pressed="false">
                    <svg id="showPasswordIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <svg id="hidePasswordIcon" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587A2 2 0 0012 14a2 2 0 001.416-.587"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.363 5.365A9.466 9.466 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-2.099 3.592"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.228 6.232C4.518 7.509 3.226 9.53 2.458 12c1.274 4.057 5.064 7 9.542 7 1.446 0 2.822-.306 4.064-.856"></path>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        @if(session('login_captcha_required'))
            <div class="mt-4">
                <x-recaptcha />
            </div>
        @endif

        @if(session('account_locked'))
            <div class="mt-4">
                <a class="underline text-sm font-medium text-indigo-600" href="{{ route('account.unlock') }}">{{ __('Verify My Identity') }}</a>
            </div>
        @endif

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const showIcon = document.getElementById('showPasswordIcon');
            const hideIcon = document.getElementById('hidePasswordIcon');

            toggleButton?.addEventListener('click', function () {
                const isPasswordVisible = passwordInput.type === 'text';

                passwordInput.type = isPasswordVisible ? 'password' : 'text';
                toggleButton.setAttribute('aria-pressed', String(!isPasswordVisible));
                toggleButton.setAttribute('aria-label', isPasswordVisible ? @json(__('Show password')) : @json(__('Hide password')));
                showIcon.classList.toggle('hidden', !isPasswordVisible);
                hideIcon.classList.toggle('hidden', isPasswordVisible);
            });
        });
    </script>
</x-guest-layout>
