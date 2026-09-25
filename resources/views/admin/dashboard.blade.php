<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="dashboard-shell">
        <div class="dashboard-surface">
            <div class="dashboard-hero">
                <p class="text-sm font-semibold text-blue-600">Administration</p>
                <h3 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Welcome back, {{ Auth::user()->firstname }}.</h3>
                <p class="mt-1 text-sm text-slate-600">Review account activity and keep the PaperTrail community running smoothly.</p>
            </div>
            <div class="p-5 text-slate-900 sm:p-8">

                    @include('partials.announcements-panel')
                    
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:mb-8 lg:grid-cols-4">
                        <div class="metric-card">
                            <h4 class="metric-label">Pending registrations</h4>
                            <p class="text-2xl font-bold text-blue-600">{{ \App\Models\User::where('status', 'Pending')->count() }}</p>
                            <a href="{{ route('admin.pending-users') }}" class="metric-link">Review <span>→</span></a>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="metric-label">Total students</h4>
                            <p class="text-2xl font-bold text-green-600">{{ \App\Models\User::where('role', 'Student')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Student']) }}" class="metric-link">Manage <span>→</span></a>
                        </div>

                        <div class="metric-card">
                            <h4 class="metric-label">Total leaders</h4>
                            <p class="text-2xl font-bold text-indigo-600">{{ \App\Models\User::where('role', 'Leader')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Leader']) }}" class="metric-link">Manage <span>→</span></a>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="metric-label">Total advisers</h4>
                            <p class="text-2xl font-bold text-purple-600">{{ \App\Models\User::where('role', 'Teacher')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Teacher']) }}" class="metric-link">Manage <span>→</span></a>
                        </div>
                    </div>

                    <hr class="mb-8 border-slate-200" />

                    <div class="w-full max-w-md">
                        <a href="{{ route('admin.announcements') }}" class="group flex flex-col items-start rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white hover:shadow-md">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                </svg>
                            </div>
                            <h3 class="mb-1 text-xl font-bold text-slate-800">Admin announcements</h3>
                            <p class="mb-4 text-sm text-slate-600">Create important system-wide announcements for every user.</p>

                            <span class="rounded-lg bg-blue-100 px-4 py-2 text-xs font-semibold text-blue-700 transition group-hover:bg-blue-200">
                                Manage admin announcements
                            </span>
                        </a>
                    </div>
            </div>
        </div>
    </div>
</x-app-layout>
