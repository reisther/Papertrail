<section class="space-y-6">
    <header class="profile-panel-heading">
        <span class="profile-panel-icon bg-red-50 text-red-600" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-10 4v5m4-5v5M9 7l1-3h4l1 3m-9 0 1 13h10l1-13"/></svg>
        </span>
        <div>
            <h2>{{ __('Delete account') }}</h2>
            @if($user->isTeacher())
                <p>Your account and adviser archive access will be removed after 30 days. Students keep their original group files and chats.</p>
            @else
                <p>Your account will be removed after 30 days. Shared group files and chat history remain available to the adviser.</p>
            @endif
        </div>
    </header>

    <div class="profile-danger-action">
        @if($user->isTeacher())
            <p class="text-red-700">Warning: your adviser archive and access to its files and chats will be removed when deletion completes. The files and chats belong to student groups, so their originals will remain available to students. Your profile picture, ID document, and adviser schedule will be deleted. You can cancel the deletion by signing in during the 30-day period.</p>
        @else
            <p>You will be signed out when you request deletion. You can recover your account for 30 days. Shared group files and chat history will remain available to your adviser, including in archived groups.</p>
        @endif
        <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('Delete account') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ $user->isTeacher() ? __('Confirm adviser account deletion') : __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                @if($user->isTeacher())
                    Enter your password twice to continue. You will be asked once more before deletion is scheduled. After 30 days, your adviser archive access, profile picture, ID document, and adviser schedule will be removed; students will keep their original group files and chats.
                @else
                    {{ __('Your account and personal details will be removed after 30 days. Shared group files and chats will remain available to your adviser. Signing in and confirming during that time will cancel the request. Enter your password to continue.') }}
                @endif
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

            @if($user->isTeacher())
                <div class="mt-4">
                    <x-input-label for="password_confirmation" value="{{ __('Enter password again') }}" />
                    <x-text-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="{{ __('Enter password again') }}"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password_confirmation')" class="mt-2" />
                </div>
            @endif

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ $user->isTeacher() ? __('Continue') : __('Schedule Deletion') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    @if($user->isTeacher())
        <x-modal name="confirm-adviser-deletion-final" :show="session('show_adviser_deletion_final_confirmation', false)" focusable>
            <form method="post" action="{{ route('profile.confirm-destroy') }}" class="p-5 sm:p-6">
                @csrf

                <h2 class="text-lg font-medium text-red-700">Are you sure you want to delete your adviser account?</h2>
                <p class="mt-2 text-sm text-gray-600">
                    This will sign you out and start a 30-day recovery period. After that, your account, profile picture, ID document, adviser schedule, and adviser archive access will be permanently removed. Student groups will keep their original files and chat history.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-secondary-button type="submit" name="decision" value="cancel">Cancel</x-secondary-button>
                    <x-danger-button class="ms-3" name="decision" value="confirm">Yes, schedule deletion</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
</section>
