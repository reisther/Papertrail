<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">My Advisers</h2>
                        <a href="{{ route('advisers.title-submission') }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                            Find More Advisers
                        </a>
                    </div>

                    @if ($advisers->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($advisers as $relationship)
                                @php
                                    $isArchived = filled($relationship->archived_at);
                                    $group = $relationship->student?->ownedProjects?->first();
                                    $archivedRoom = $group?->chatRooms?->first();
                                @endphp
                                <div class="{{ $isArchived ? 'bg-gray-50' : 'bg-white' }} border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $relationship->adviser->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $relationship->adviser->course }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm"><span class="font-medium">Campus:</span> {{ $relationship->adviser->campus }}</p>
                                        <p class="text-sm"><span class="font-medium">Email:</span> {{ $relationship->adviser->email }}</p>
                                        <p class="text-sm"><span class="font-medium">Approved:</span> {{ $relationship->responded_at->format('M j, Y') }}</p>
                                    </div>

                                    @if ($relationship->response_message)
                                        <div class="bg-gray-50 p-3 rounded border">
                                            <p class="text-sm text-gray-700">
                                                <span class="font-medium">Message:</span> {{ $relationship->response_message }}
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $isArchived ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                            {{ $isArchived ? 'Archived Adviser' : 'Active Adviser' }}
                                        </span>
                                        @if($isArchived)
                                            <a href="{{ $archivedRoom ? route('chat.archived', ['room' => $archivedRoom->id]) : route('chat.archived') }}"
                                               class="inline-flex h-9 items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                                                Archived chats
                                            </a>
                                        @else
                                            <form method="POST" action="{{ route('advisers.release', $relationship) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                    Remove Adviser
                                                </button>
                                            </form>
                                        @endif
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Advisers Yet</h3>
                            <p class="text-gray-500 mb-6">You haven't been approved by any advisers yet.</p>
                            <a href="{{ route('advisers.title-submission') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition-colors">
                                Find Advisers
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
