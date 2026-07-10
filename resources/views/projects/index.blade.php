<x-app-layout>
    @php
        $isArchivedProjectsPage = $isArchivedProjectsPage ?? false;
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isArchivedProjectsPage ? __('Archived Projects') : __('My Projects') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $isArchivedProjectsPage ? route('projects.index') : route('projects.archived') }}"
                   class="inline-flex items-center justify-center rounded-md {{ $isArchivedProjectsPage ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-800 hover:bg-gray-900 text-white' }} px-4 py-2 text-sm font-medium transition-colors">
                    {{ $isArchivedProjectsPage ? 'Active Projects' : 'Archived Projects' }}
                </a>
                @if($isArchivedProjectsPage && (Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher()))
                    <a href="{{ route('chat.archived') }}"
                       class="inline-flex items-center justify-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200">
                        Archived Chats
                    </a>
                @endif
                @if(!$isArchivedProjectsPage && (Auth::user()->canLeadGroup() || Auth::user()->isAdmin()))
                    <a href="{{ route('projects.create') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        + New Project
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(!$isArchivedProjectsPage && Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist())
                @include('partials.leader-create-group-card')
            @elseif ($projects->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($projects as $project)
                        @php
                            $displayStatus = $project->is_archived_for_current_user ? 'archived' : $project->status;
                            $statusIconWrapperClass = match ($displayStatus) {
                                'active' => 'bg-green-100',
                                'completed' => 'bg-blue-100',
                                'archived' => 'bg-gray-100',
                                default => 'bg-yellow-100',
                            };
                            $statusBadgeClass = match ($displayStatus) {
                                'active' => 'bg-green-100 text-green-800',
                                'completed' => 'bg-blue-100 text-blue-800',
                                'archived' => 'bg-gray-100 text-gray-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                            $statusDotClass = match ($displayStatus) {
                                'active' => 'bg-green-400',
                                'completed' => 'bg-blue-400',
                                'archived' => 'bg-gray-400',
                                default => 'bg-yellow-400',
                            };
                        @endphp
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-all duration-200 border border-gray-200">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $statusIconWrapperClass }}">
                                                @if($displayStatus === 'active')
                                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($displayStatus === 'completed')
                                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($displayStatus === 'archived')
                                                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                                                        <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <a href="{{ route('projects.show', $project) }}" 
                                                   class="hover:text-blue-600 transition-colors">
                                                    {{ $project->title }}
                                                </a>
                                            </h3>
                                            <p class="text-gray-600 text-sm line-clamp-2">
                                                {{ $project->description ?? 'No description provided.' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusBadgeClass }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $statusDotClass }}"></span>
                                            {{ $project->is_archived_for_current_user ? 'Archived' : ucfirst($project->status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $project->documents_count }} files
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                            {{ $project->folders_count }} folders
                                        </div>
                                    </div>
                                    <span>{{ $project->formatted_list_size }}</span>
                                </div>

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <div>
                                        <span class="font-medium">Owner:</span> {{ $project->owner->name }}
                                    </div>
                                    @if($project->adviser)
                                        <div>
                                            <span class="font-medium">Adviser:</span> {{ $project->adviser->name }}
                                        </div>
                                    @endif
                                </div>

                                @if($project->due_date)
                                    <div class="text-sm text-gray-500 mb-4">
                                        <span class="font-medium">Due:</span> 
                                        <span class="{{ $project->due_date->isPast() ? 'text-red-600' : 'text-gray-600' }}">
                                            {{ $project->due_date->format('M j, Y') }}
                                        </span>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <div class="text-xs text-gray-500">
                                        Updated {{ $project->updated_at->diffForHumans() }}
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('projects.show', $project) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            View
                                        </a>
                                        @if($isArchivedProjectsPage && (Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher()))
                                            @php($archivedChatRoom = $project->chatRooms->first())
                                            <a href="{{ $archivedChatRoom ? route('chat.archived', ['room' => $archivedChatRoom->id]) : route('chat.archived') }}"
                                               class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                Chats
                                            </a>
                                        @endif
                                        @if($project->canEdit(Auth::user()))
                                            <a href="{{ route('projects.edit', $project) }}" 
                                               class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                Edit
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $projects->links() }}
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        @if($isArchivedProjectsPage)
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">No Archived Projects Yet</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">Archived groups and project files will appear here after they are archived.</p>
                        @elseif(Auth::user()->canLeadGroup())
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">You haven't created a group yet</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">Create your group first so you can manage projects, find an adviser, schedule meetings, and make chat rooms.</p>
                        @elseif(Auth::user()->isStudent())
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">You're not in a group yet</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">Ask your leader to send you the invitation link for your group. Once you join, your group project and files will appear here.</p>
                        @else
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">No Projects Yet</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">Get started by creating your first project to organize and share your documents with your adviser.</p>
                        @endif
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            @if(!$isArchivedProjectsPage && (Auth::user()->canLeadGroup() || Auth::user()->isAdmin()))
                                <a href="{{ Auth::user()->canLeadGroup() ? route('group-description.show') : route('projects.create') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{ Auth::user()->canLeadGroup() ? 'Create Now' : 'Create Your First Project' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
