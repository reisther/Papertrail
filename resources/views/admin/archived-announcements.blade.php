<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Archived announcements</h2>
    </x-slot>
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-4">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-700">Back to dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900">Archived announcements</h1>
        <p class="text-sm text-gray-600">These announcements are hidden from everyone except you. If you change your mind, retrieve an announcement to make it visible to its original audience again. Archived announcements stay here until you retrieve them. Deleted announcements are permanently removed after 30 days.</p>
        @if(session('success'))
            <p class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</p>
        @endif
        @forelse($announcements as $announcement)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <p class="text-xs font-semibold text-gray-500">
                    @if($announcement->delete_after)
                        Pending deletion — permanently deleted {{ $announcement->delete_after->format('M j, Y g:i A') }} ({{ (int) ceil(now()->diffInSeconds($announcement->delete_after) / 86400) }} days remaining)
                    @else
                        Archived — no deletion scheduled
                    @endif
                </p>
                <p class="whitespace-pre-line text-sm text-gray-800">{{ $announcement->message }}</p>
                @if($announcement->attachment_path)
                    @include('partials.announcement-attachment', ['announcement' => $announcement])
                @endif
                <form method="POST" action="{{ route('announcements.restore', $announcement->id) }}">
                    @csrf
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Retrieve announcement</button>
                </form>
            </div>
        @empty
            <p class="rounded-lg border border-dashed p-8 text-center text-gray-500">You have no archived or deleted announcements to retrieve.</p>
        @endforelse
    </div>
</x-app-layout>
