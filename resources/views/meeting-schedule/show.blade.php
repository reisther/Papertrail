<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Meeting Details
            </h2>
            <div class="flex space-x-3">
                @if($meetingSchedule->canEdit(Auth::user()))
                    <a href="{{ route('meeting-schedule.edit', $meetingSchedule) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                @endif
                <a href="{{ route('meeting-schedule.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Back to Calendar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Meeting Title and Status -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $meetingSchedule->title }}</h1>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                    @if($meetingSchedule->status === 'scheduled') bg-blue-100 text-blue-800
                                    @elseif($meetingSchedule->status === 'completed') bg-green-100 text-green-800
                                    @elseif($meetingSchedule->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($meetingSchedule->status) }}
                                </span>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                    @if($meetingSchedule->type === 'meeting') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ ucwords(str_replace('_', ' ', $meetingSchedule->type)) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Information Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Date and Time -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Schedule
                                </h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Start:</span>
                                        <span class="font-medium">{{ $meetingSchedule->start_time->format('M j, Y g:i A') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">End:</span>
                                        <span class="font-medium">{{ $meetingSchedule->end_time->format('M j, Y g:i A') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Duration:</span>
                                        <span class="font-medium">{{ $meetingSchedule->duration }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Participants -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Group
                                </h3>
                                <div class="space-y-3">
                                    @if($meetingSchedule->project)
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-blue-600">G</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $meetingSchedule->project->title }}</div>
                                            <div class="text-sm text-gray-600">Group Code</div>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-green-600">A</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $meetingSchedule->adviser->name }}</div>
                                            <div class="text-sm text-gray-600">Adviser</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Meeting -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Meeting
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="text-sm text-gray-600 mb-1">Meeting Platform:</div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium text-gray-600">Manual Link</span>
                                        </div>
                                    </div>

                                    @if($meetingSchedule->effective_meeting_link)
                                        <div>
                                            <div class="text-sm text-gray-600 mb-2">Online Meeting:</div>
                                            <div class="flex flex-col space-y-2">
                                                <a href="{{ $meetingSchedule->effective_meeting_link }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Join Meeting
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-gray-500 italic">No meeting link specified</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Project Information -->
                            @if($meetingSchedule->project)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Group Code
                                    </h3>
                                    <div>
                                        <div class="font-medium text-lg">{{ $meetingSchedule->project->title }}</div>
                                        @if($meetingSchedule->project->description)
                                            <div class="text-gray-600 mt-2">{{ Str::limit($meetingSchedule->project->description, 150) }}</div>
                                        @endif
                                        <a href="{{ route('projects.show', $meetingSchedule->project) }}" 
                                           class="inline-flex items-center text-blue-600 hover:text-blue-800 mt-2">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            View Project
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <!-- Additional Information -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Additional Information
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Created by:</span>
                                        <span class="font-medium">{{ $meetingSchedule->creator->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Created on:</span>
                                        <span class="font-medium">{{ $meetingSchedule->created_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                    @if($meetingSchedule->updated_at != $meetingSchedule->created_at)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Last updated:</span>
                                            <span class="font-medium">{{ $meetingSchedule->updated_at->format('M j, Y g:i A') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($meetingSchedule->description)
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Description</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $meetingSchedule->description }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    @if($meetingSchedule->canEdit(Auth::user()))
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-3">
                                    <a href="{{ route('meeting-schedule.edit', $meetingSchedule) }}" 
                                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                        Edit Meeting
                                    </a>
                                </div>
                                <button onclick="confirmDelete()" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    Delete Meeting
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Meeting</h3>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Are you sure you want to delete this meeting? This action cannot be undone.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('meeting-schedule.destroy', $meetingSchedule) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                            Delete Meeting
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</x-app-layout>
