<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">My Students</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center justify-center bg-blue-100 text-blue-800 px-3 py-2 rounded-md text-sm font-medium leading-none min-h-9">
                                {{ $students->count() }} Students
                            </span>
                            <span class="inline-flex items-center justify-center bg-gray-100 text-gray-700 px-3 py-2 rounded-md text-sm font-medium leading-none min-h-9">
                                {{ $archivedStudents->count() }} Archived
                            </span>
                            <a href="{{ route('advisers.pending-requests') }}" 
                               class="inline-flex items-center justify-center bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm transition-colors min-h-9">
                                View Pending Requests
                            </a>
                        </div>
                    </div>

                    @if ($students->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($students as $relationship)
                                @php
                                    $leader = $relationship->student;
                                    $group = $leader->ownedProjects->first();
                                @endphp
                                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow flex h-full flex-col">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-lg font-semibold text-gray-900 truncate">{{ $group?->title ?? 'No group name yet' }}</h4>
                                            <p class="text-sm text-gray-600">{{ $leader->course }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm"><span class="font-medium">Leader:</span> {{ $leader->name }}</p>
                                        <p class="text-sm"><span class="font-medium">Section:</span> {{ $leader->section }}</p>
                                        <p class="text-sm"><span class="font-medium">Course:</span> {{ $leader->course }}</p>
                                        <p class="text-sm"><span class="font-medium">Approved:</span> {{ $relationship->responded_at->format('M j, Y') }}</p>
                                    </div>

                                    @if ($relationship->message)
                                        <div class="bg-gray-50 p-3 rounded border mb-3">
                                            <p class="text-sm text-gray-700">
                                                <span class="font-medium">Initial Message:</span> {{ $relationship->message }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($relationship->response_message)
                                        <div class="bg-blue-50 p-3 rounded border mb-3">
                                            <p class="text-sm text-blue-700">
                                                <span class="font-medium">Your Response:</span> {{ $relationship->response_message }}
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <div class="mt-auto flex flex-col gap-3 border-t border-gray-100 pt-4">
                                        <span class="inline-flex w-fit items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Active Group
                                        </span>
                                        <div class="grid grid-cols-1 items-stretch gap-2 sm:grid-cols-3">
                                            @if($group)
                                                <a href="{{ route('projects.show', $group) }}"
                                                   class="inline-flex h-10 w-full items-center justify-center rounded-md bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                                    Project
                                                </a>
                                            @else
                                                <span class="hidden sm:block"></span>
                                            @endif
                                            <form method="POST"
                                                  action="{{ route('advisers.archive', $relationship) }}"
                                                  class="contents"
                                                  onsubmit="return confirm('Are you sure you want to archive this group? The chats and uploaded files will move to read-only archive history, and students will no longer be able to chat with you in this group.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                                    Archive
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="{{ route('advisers.release', $relationship) }}"
                                                  class="contents"
                                                  onsubmit="return confirm('Are you sure you want to stop advising this group');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-10 w-full items-center justify-center whitespace-nowrap rounded-md bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100 sm:text-sm">
                                                    Stop Advising
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Students Yet</h3>
                            <p class="text-gray-500 mb-6">You haven't approved any student requests yet.</p>
                            <a href="{{ route('advisers.pending-requests') }}" 
                               class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-md transition-colors">
                                Check Pending Requests
                            </a>
                        </div>
                    @endif

                    @if ($archivedStudents->count() > 0)
                        <div class="mt-10 border-t border-gray-200 pt-8">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-900">Archived History</h3>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">Archived chats are read-only</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($archivedStudents as $relationship)
                                    @php
                                        $leader = $relationship->student;
                                        $group = $leader->ownedProjects->first();
                                        $archivedRoom = $group?->chatRooms?->first();
                                    @endphp
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 flex h-full flex-col">
                                        <div class="mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $group?->title ?? 'No group name yet' }}</h4>
                                            <p class="text-sm text-gray-600">Leader: {{ $leader->name }}</p>
                                        </div>
                                        <div class="space-y-2 mb-4">
                                            <p class="text-sm"><span class="font-medium">Course:</span> {{ $leader->course }}</p>
                                            <p class="text-sm"><span class="font-medium">Approved:</span> {{ optional($relationship->responded_at)->format('M j, Y') }}</p>
                                            <p class="text-sm"><span class="font-medium">Archived:</span> {{ optional($relationship->archived_at)->format('M j, Y') }}</p>
                                        </div>
                                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4">
                                            <span class="inline-flex w-fit items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                                                Archived Group
                                            </span>
                                            <div class="flex flex-wrap gap-2">
                                                @if($group)
                                                    <a href="{{ route('group-description.details', $group) }}"
                                                       class="inline-flex h-10 items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                                        Group Details
                                                    </a>
                                                    <a href="{{ route('projects.show', $group) }}"
                                                       class="inline-flex h-10 items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                                        Archived files
                                                    </a>
                                                @endif
                                                <a href="{{ $archivedRoom ? route('chat.archived', ['room' => $archivedRoom->id]) : route('chat.archived') }}"
                                                   class="inline-flex h-10 items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                                    Archived chats
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
