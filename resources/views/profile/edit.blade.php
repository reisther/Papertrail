<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="app-page-narrow">
        <div class="mb-7"><p class="text-sm font-semibold text-blue-600">Account settings</p><h1 class="app-heading mt-1">Your profile</h1><p class="app-subheading">Manage your personal details, password, and account preferences.</p></div>
        <div class="space-y-5">
            <div class="content-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="content-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if(!Auth::user()->isAdmin())
                <div class="content-card">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
