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
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
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
