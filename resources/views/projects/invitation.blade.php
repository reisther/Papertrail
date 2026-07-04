<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Group Invitation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-6a4 4 0 11-8 0 4 4 0 018 0zm-8 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>

                <p class="text-sm font-semibold uppercase text-blue-700">You were invited to join</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $project->title }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Leader: {{ $project->owner?->name ?? 'Group leader' }}
                </p>

                @if($alreadyInGroup)
                    <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                        You are already part of this group.
                    </div>
                    <a href="{{ route('projects.show', $project) }}" class="mt-6 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Open Group
                    </a>
                @else
                    <p class="mt-6 text-sm text-gray-600">
                        Choose whether you want to join this group as a member.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <form method="POST" action="{{ route('projects.accept-invitation.store', $invitation->token) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:w-auto">
                                Join Group
                            </button>
                        </form>

                        <form method="POST" action="{{ route('projects.decline-invitation', $invitation->token) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">
                                Decline
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
