<x-app-layout>
    @php
        $user = Auth::user();
        $activeStudentProjectsQuery = $user->activeAccessibleProjects()
            ->where('owner_id', '!=', $user->id);
        $activeStudentProjectsCount = (clone $activeStudentProjectsQuery)->count();
    @endphp

    <x-slot name="header">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="dashboard-shell">
        <div class="dashboard-surface">
            <div class="dashboard-hero">
                <p class="text-sm font-semibold text-blue-600">Adviser workspace</p>
                <h3 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Welcome back, {{ Auth::user()->firstname }}.</h3>
                <p class="mt-1 text-sm text-slate-600">Keep an eye on your students, requests, and current projects.</p>
            </div>
            <div class="p-5 text-slate-900 sm:p-8">

                    @include('partials.announcements-panel')
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="metric-card">
                            <h4 class="metric-label">My students</h4>
                            <p class="metric-value">{{ Auth::user()->students()->count() }}</p>
                            <a href="{{ route('advisers.my-students') }}" class="metric-link">View all <span>→</span></a>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="metric-label">Pending requests</h4>
                            <p class="metric-value">{{ Auth::user()->studentRequests()->pending()->count() }}</p>
                            <a href="{{ route('advisers.pending-requests') }}" class="metric-link">Review <span>→</span></a>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="metric-label">Student projects</h4>
                            <p class="metric-value">{{ $activeStudentProjectsCount }}</p>
                            <a href="{{ route('projects.index') }}" class="metric-link">View projects <span>→</span></a>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:mt-8 sm:grid-cols-2 sm:gap-6">
                        <a href="{{ route('todo.index') }}" class="group block rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white hover:shadow-md">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
                                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"></path>
                                </svg>
                            </div>
                            <h3 class="mb-1 text-xl font-bold text-slate-800">To-do lists</h3>
                            <p class="mb-4 text-sm text-slate-600">Create per-chapter tasks for student groups.</p>
                            <span class="rounded-lg bg-blue-100 px-4 py-2 text-xs font-semibold text-blue-700 transition group-hover:bg-blue-200">Open to-do lists</span>
                        </a>

                        <a href="{{ route('advisers.progress-tracker') }}" class="group block rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white hover:shadow-md">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100">
                                <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6"></path>
                                </svg>
                            </div>
                            <h3 class="mb-1 text-xl font-bold text-slate-800">Progress tracker</h3>
                            <p class="mb-4 text-sm text-slate-600">Monitor advisee chapter progress and task completion.</p>
                            <span class="rounded-lg bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700 transition group-hover:bg-emerald-200">View progress</span>
                        </a>
                    </div>

                    <div class="quiet-panel mt-6">
                        <h4 class="font-bold text-slate-800">Your information</h4>
                        <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-2">
                            <p><strong>Campus:</strong> {{ Auth::user()->campus }}</p>
                            <p><strong>Department:</strong> {{ Auth::user()->course }}</p>
                            <p><strong>Section:</strong> {{ Auth::user()->section }}</p>
                            <p><strong>Status:</strong> 
                                <span class="status-pill {{ Auth::user()->status === 'Verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ Auth::user()->status }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if(Auth::user()->studentRequests()->pending()->count() > 0)
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">Recent Student Requests</h4>
                            <div class="space-y-2">
                                @foreach(Auth::user()->studentRequests()->pending()->with('student')->latest()->take(3)->get() as $request)
                                    <div class="flex flex-col gap-2 rounded border bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-gray-900">{{ $request->student->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $request->student->course }} - {{ $request->student->section }}</p>
                                        </div>
                                        <span class="shrink-0 text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('advisers.pending-requests') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">View all pending requests →</a>
                            </div>
                        </div>
                    @endif

                    @php
                        $studentProjects = (clone $activeStudentProjectsQuery)
                            ->with('owner')
                            ->withCount('documents')
                            ->latest('updated_at')
                            ->take(3)
                            ->get();
                    @endphp

                    @if($studentProjects->count() > 0)
                        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">Recent Student Projects</h4>
                            <div class="space-y-2">
                                @foreach($studentProjects as $project)
                                    <div class="flex flex-col gap-2 rounded border bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-gray-900">{{ $project->title }}</p>
                                            <p class="text-sm text-gray-600">by {{ $project->owner->name }} • {{ $project->documents_count }} files</p>
                                        </div>
                                        <div class="shrink-0 sm:text-right">
                                            <a href="{{ route('projects.show', $project) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">View →</a>
                                            <p class="text-xs text-gray-500">{{ $project->updated_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('projects.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">View all student projects →</a>
                            </div>
                        </div>
                    @endif
            </div>
        </div>
    </div>
</x-app-layout>
