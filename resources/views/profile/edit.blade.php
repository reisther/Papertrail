<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="profile-header-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.5 19.5v-1.25A3.25 3.25 0 0 0 12.25 15h-5A3.25 3.25 0 0 0 4 18.25v1.25M9.75 11.5A3.25 3.25 0 1 0 9.75 5a3.25 3.25 0 0 0 0 6.5ZM19 8v6m3-3h-6" /></svg>
            </span>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ __('Profile') }}</h2>
        </div>
    </x-slot>

    <div class="profile-page">
        <div class="profile-page-intro">
            <p class="profile-eyebrow">Account settings</p>
            <h1 class="app-heading mt-1">Your profile</h1>
            <p class="app-subheading">Keep your details current and manage your account security in one place.</p>
        </div>

        <div class="profile-layout">
            <aside class="profile-summary" aria-label="Account overview">
                <div class="profile-summary-avatar">
                    @if($user->profile_picture_path)
                        <img src="{{ route('profile.picture', $user) }}?v={{ $user->updated_at?->timestamp }}" alt="{{ $user->name }}">
                    @else
                        <span>{{ strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-slate-900">{{ $user->name }}</p>
                    <p class="mt-0.5 truncate text-sm text-slate-500">{{ $user->email }}</p>
                </div>
                <div class="profile-summary-meta">
                    <span class="profile-role-badge">{{ $user->role_display_name }}</span>
                    @if($user->email_verified_at)
                        <span class="profile-verified-badge">
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.97a.75.75 0 0 0-1.06-1.06L9.25 10.44 7.78 8.97a.75.75 0 0 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" /></svg>
                            Verified
                        </span>
                    @endif
                </div>
                <nav class="profile-section-nav" aria-label="Profile sections">
                    <a href="#personal-details">Personal details</a>
                    <a href="#account-security">Account security</a>
                    @if(!Auth::user()->isAdmin())
                        <a href="#danger-zone">Account deletion</a>
                    @endif
                </nav>
            </aside>

            <div class="profile-settings">
                <div class="profile-panel" id="personal-details">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="profile-panel" id="account-security">
                    @include('profile.partials.update-password-form')
                </div>

                @if(!Auth::user()->isAdmin())
                    <div class="profile-panel profile-danger-panel" id="danger-zone">
                        @include('profile.partials.delete-user-form')
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
