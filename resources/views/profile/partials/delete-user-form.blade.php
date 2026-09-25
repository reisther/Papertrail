<section class="space-y-6">
    <header class="profile-panel-heading">
        <span class="profile-panel-icon bg-red-50 text-red-600" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-10 4v5m4-5v5M9 7l1-3h4l1 3m-9 0 1 13h10l1-13"/></svg>
        </span>
        <div>
            <h2>{{ __('Delete account') }}</h2>
            <p>{{ __('Permanently remove your account and all its data. This cannot be undone.') }}</p>
        </div>
    </header>

    <div class="profile-danger-action">
        <p>Make sure you have saved any information you want to keep before continuing.</p>
        <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('Delete account') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
