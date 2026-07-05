<x-app-layout>
    @php
        $user = Auth::user();
        $group = $user->canLeadGroup()
            ? $user->ownedProjects()->latest()->first()
            : $user->joinedProjects()->latest('project_members.joined_at')->first();
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->canLeadGroup() ? __('Leader Dashboard') : __('Member Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ $user->firstname }} {{ $user->lastname }}!</h3>
                    <p class="text-gray-600 mb-6">You are logged in as a {{ $user->canLeadGroup() ? 'Leader' : 'Member' }}.</p>

                    @include('partials.announcements-panel')

                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-6 p-6 bg-indigo-50 border border-indigo-100 rounded-lg">
                        <h4 class="font-semibold text-indigo-900">Group Information</h4>

                        @if($user->canLeadGroup() && !$group)
                            <div class="mt-4 rounded-lg bg-white border border-indigo-100 p-6 text-center">
                                <h5 class="text-lg font-semibold text-indigo-900">Don't have a group yet?</h5>
                                <p class="mt-2 text-sm text-indigo-800">Create your group first so you can invite members, manage tasks, and organize your capstone work.</p>
                                <a href="{{ route('group-description.show') }}"
                                   class="inline-flex items-center mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Create Now
                                </a>
                            </div>
                        @elseif($user->canLeadGroup())
                            <div class="mt-4">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div>
                                        <p class="text-2xl font-bold text-indigo-700">{{ $group->title }}</p>
                                        <p class="mt-2 text-sm text-indigo-900 whitespace-pre-line">{{ $group->description ?: 'No group description yet.' }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ route('group-description.show') }}"
                                           class="inline-flex items-center justify-center bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-4 py-2 rounded-md text-sm font-medium">
                                            Show
                                        </a>
                                        <a href="{{ route('group-description.show', ['edit' => 1]) }}"
                                           class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @elseif($group)
                            <div class="mt-4">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div>
                                        <p class="text-2xl font-bold text-indigo-700">{{ $group->title }}</p>
                                        <p class="mt-2 text-sm text-indigo-900">{{ $group->description ?: 'No group description yet.' }}</p>
                                    </div>
                                    <a href="{{ route('group-description.show') }}"
                                       class="inline-flex items-center justify-center bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-4 py-2 rounded-md text-sm font-medium">
                                        Show
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="mt-2 text-sm text-indigo-900">You're not in a group yet. Ask your leader to send you the invitation link for your group.</p>
                        @endif

                        @if($user->canLeadGroup() && $group)
                            <div class="mt-6 pt-4 border-t border-indigo-200">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-900">Share Group Link</p>
                                        <p class="text-sm text-indigo-800 mt-1">Generate a link so members can join your group.</p>
                                    </div>
                                    <form method="POST" action="{{ route('group-description.share-link') }}">
                                        @csrf
                                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium">
                                            Generate Link
                                        </button>
                                    </form>
                                </div>

                                @if(session('invite_link'))
                                    <div class="mt-4">
                                        <label for="dashboardInviteLink" class="block text-sm font-medium text-indigo-900 mb-2">Invitation Link</label>
                                        <div class="flex gap-2">
                                            <input id="dashboardInviteLink" type="text" readonly value="{{ session('invite_link') }}"
                                                   class="flex-1 rounded-md border-indigo-200 bg-white text-sm">
                                            <button type="button" onclick="copyDashboardInviteLink()"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                    
                    <div class="grid grid-cols-1 {{ $user->isStudentGroupRole() ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-blue-800">My Projects</h4>
                            <p class="text-2xl font-bold text-blue-600">{{ $user->accessibleProjects()->count() }}</p>
                            <a href="{{ route('projects.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">View all -></a>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-green-800">Submissions</h4>
                            <p class="text-2xl font-bold text-green-600">0</p>
                            @if($user->canLeadGroup())
                                <a href="{{ route('advisers.title-submission') }}" class="text-green-600 hover:text-green-800 text-sm">View all -></a>
                            @endif
                        </div>

                        @if($user->isStudentGroupRole())
                            <div class="bg-indigo-50 p-6 rounded-lg">
                                <h4 class="font-semibold text-indigo-800">Adviser Tasks</h4>
                                <p class="text-2xl font-bold text-indigo-600">
                                    {{ ($group && $group->adviser_id) ? $group->tasks()->where('adviser_id', $group->adviser_id)->count() : 0 }}
                                </p>
                                @if($group)
                                    <a href="{{ route('todo.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">Open checklist -></a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Your Information:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                            <p><strong>Campus:</strong> {{ $user->campus }}</p>
                            <p><strong>Course:</strong> {{ $user->course }}</p>
                            <p><strong>Section:</strong> {{ $user->section }}</p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 py-1 rounded text-xs {{ $user->status === 'Verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $user->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyDashboardInviteLink() {
            const input = document.getElementById('dashboardInviteLink');
            input.select();
            document.execCommand('copy');
        }
    </script>
</x-app-layout>
