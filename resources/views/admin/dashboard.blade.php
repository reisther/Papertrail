<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}!</h3>
                    <p class="text-gray-600 mb-6">You are logged in as an Administrator.</p>

                    @include('partials.announcements-panel')
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-blue-800">Pending Registrations</h4>
                            <p class="text-2xl font-bold text-blue-600">{{ \App\Models\User::where('status', 'Pending')->count() }}</p>
                            <a href="{{ route('admin.pending-users') }}" class="text-blue-600 hover:text-blue-800 text-sm">Review Documents →</a>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-green-800">Total Students</h4>
                            <p class="text-2xl font-bold text-green-600">{{ \App\Models\User::where('role', 'Student')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Student']) }}" class="text-green-600 hover:text-green-800 text-sm">Manage →</a>
                        </div>

                        <div class="bg-indigo-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-indigo-800">Total Leaders</h4>
                            <p class="text-2xl font-bold text-indigo-600">{{ \App\Models\User::where('role', 'Leader')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Leader']) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">Manage →</a>
                        </div>
                        
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-purple-800">Total Teachers</h4>
                            <p class="text-2xl font-bold text-purple-600">{{ \App\Models\User::where('role', 'Teacher')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Teacher']) }}" class="text-purple-600 hover:text-purple-800 text-sm">Manage →</a>
                        </div>
                    </div>

                    <hr class="border-gray-200 mb-8" />

                    <div class="max-w-md">
                        <a href="{{ route('admin.announcements') }}" class="block border-2 border-purple-300 bg-purple-50 p-6 rounded-2xl shadow-sm flex flex-col items-start hover:bg-purple-100 transition group">
                            <div class="w-12 h-12 bg-purple-200 rounded-xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">Admin Announcements</h3>
                            <p class="text-sm text-gray-500 mb-4">Create important system-wide announcements for every user.</p>

                            <span class="text-xs font-semibold text-purple-600 bg-purple-100 group-hover:bg-purple-200 px-4 py-2 rounded-lg transition">
                                Manage admin announcements
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
