<x-app-layout>
    @php
        $user = Auth::user();
        $group = $user->canLeadGroup()
            ? $user->ownedProjects()->latest()->first()
            : $user->joinedProjects()->latest('project_members.joined_at')->first();
        $submissionCount = $group
            ? \App\Models\Folder::where('project_id', $group->id)
                ->whereNull('parent_id')
                ->where('name', 'Submissions')
                ->withCount('documents')
                ->first()?->documents_count ?? 0
            : 0;
        $hasActiveAdviserTasks = $group && $group->status !== 'archived' && filled($group->adviser_id);
    @endphp

    <x-slot name="header">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">
            {{ $user->canLeadGroup() ? __('Leader Dashboard') : __('Member Dashboard') }}
        </h2>
    </x-slot>

    <div class="dashboard-shell">
        <div class="dashboard-surface">
            <div class="dashboard-hero">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-blue-600">{{ $user->canLeadGroup() ? 'Group workspace' : 'Your workspace' }}</p>
                        <h3 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Welcome back, {{ $user->firstname }}.</h3>
                        <p class="mt-1 text-sm text-slate-600">Here is a quick look at your thesis work and what needs your attention.</p>
                    </div>
                    <span class="status-pill {{ $user->status === 'Verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $user->status }}</span>
                </div>
            </div>
            <div class="p-5 text-slate-900 sm:p-8">

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

                    <div class="quiet-panel mb-6">
                        <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-sm font-bold text-blue-700">01</span><h4 class="font-bold text-slate-800">Group information</h4></div>

                        @if($user->canLeadGroup() && !$group)
                            <div class="mt-4 rounded-xl border border-blue-100 bg-white p-6 text-center">
                                <h5 class="text-lg font-bold text-slate-800">Don't have a group yet?</h5>
                                <p class="mt-2 text-sm text-slate-600">Create your group first so you can invite members, manage tasks, and organize your capstone work.</p>
                                <a href="{{ route('group-description.show') }}"
                                   class="mt-4 inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                    Create Now
                                </a>
                            </div>
                        @elseif($user->canLeadGroup())
                            <div class="mt-4">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $group->title }}</p>
                                        <p class="mt-2 text-sm text-slate-600 whitespace-pre-line">{{ $group->description ?: 'No group description yet.' }}</p>
                                    </div>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <a href="{{ route('group-description.show') }}"
                                           class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700 sm:w-auto">
                                            Show
                                        </a>
                                        <a href="{{ route('group-description.show', ['edit' => 1]) }}"
                                           class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @elseif($group)
                            <div class="mt-4">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $group->title }}</p>
                                        <p class="mt-2 text-sm text-slate-600">{{ $group->description ?: 'No group description yet.' }}</p>
                                    </div>
                                    <a href="{{ route('group-description.show') }}"
                                       class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700 sm:w-auto">
                                        Show
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="mt-4 text-sm text-slate-600">You're not in a group yet. Ask your leader to send you the invitation link for your group.</p>
                        @endif

                        @if($user->canLeadGroup() && $group)
                            <div class="mt-6 pt-4 border-t border-indigo-200">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Share group link</p>
                                        <p class="mt-1 text-sm text-slate-600">Generate a link so members can join your group.</p>
                                    </div>
                                    <form method="POST" action="{{ route('group-description.share-link') }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900 sm:w-auto">
                                            Generate Link
                                        </button>
                                    </form>
                                </div>

                                @if(session('invite_link'))
                                    <div class="mt-4">
                                        <label for="dashboardInviteLink" class="block text-sm font-medium text-indigo-900 mb-2">Invitation Link</label>
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <input id="dashboardInviteLink" type="text" readonly value="{{ session('invite_link') }}"
                                                   class="min-w-0 flex-1 rounded-md border-indigo-200 bg-white text-sm">
                                            <button type="button" onclick="copyDashboardInviteLink()"
                                                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 sm:w-auto">
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                    
                    <div class="grid grid-cols-1 gap-4 {{ $user->isStudentGroupRole() ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2' }}">
                        <div class="metric-card">
                            <h4 class="metric-label">My projects</h4>
                            <p class="metric-value">{{ $user->activeAccessibleProjects()->count() }}</p>
                            <a href="{{ route('projects.index') }}" class="metric-link">View all <span>→</span></a>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="metric-label">Submissions</h4>
                            <p class="metric-value">{{ $submissionCount }}</p>
                            @if($user->canLeadGroup())
                                <a href="{{ $group ? route('projects.show', $group) : route('projects.index') }}" class="metric-link">View all <span>→</span></a>
                            @endif
                        </div>

                        @if($user->isStudentGroupRole())
                            <div class="metric-card">
                                <h4 class="metric-label">Adviser tasks</h4>
                                <p class="metric-value">
                                    {{ $hasActiveAdviserTasks ? $group->tasks()->where('adviser_id', $group->adviser_id)->count() : 0 }}
                                </p>
                                @if($hasActiveAdviserTasks)
                                    <a href="{{ route('todo.index') }}" class="metric-link">Open checklist <span>→</span></a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="quiet-panel mt-6">
                        <h4 class="font-bold text-slate-800">Your information</h4>
                        <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-2">
                            <p><strong>Campus:</strong> {{ $user->campus }}</p>
                            <p><strong>Course:</strong> {{ $user->course }}</p>
                            <p><strong>Section:</strong> {{ $user->section }}</p>
                            <p><strong>Status:</strong> 
                                <span class="status-pill {{ $user->status === 'Verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $user->status }}
                                </span>
                            </p>
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
