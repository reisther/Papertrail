@php
    $chatUnreadCount = 0;
    $notificationUnreadCount = 0;
    $studentRequestPendingCount = 0;

    if (Auth::check()) {
        $chatUnreadCount = Auth::user()->chatRooms()
            ->where('chat_rooms.is_active', true)
            ->get()
            ->sum(fn ($room) => $room->getUnreadCountForUser(Auth::user()));

        if (Auth::user()->isTeacher()) {
            $studentRequestPendingCount = Auth::user()->studentRequests()
                ->where('status', 'pending')
                ->count();
        }

        $notificationUnreadCount = \Illuminate\Support\Facades\Schema::hasTable('app_notifications')
            ? \App\Models\AppNotification::where('user_id', Auth::id())->whereNull('read_at')->count()
            : $studentRequestPendingCount;
    }
@endphp

 <!-- Header Navigation -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd"
                                    d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="ml-3 text-xl font-semibold text-gray-900">PaperTrail</span>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="hidden min-w-0 flex-1 items-center justify-center gap-4 xl:flex">
                    @auth
                        @if(Auth::user()->role === 'Admin')
                            <a href="{{ route('admin.dashboard') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-900 transition-colors hover:text-blue-600 xl:px-3">Dashboard</a>
                        @elseif(Auth::user()->role === 'Teacher')
                            <a href="{{ route('teacher.dashboard') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-900 transition-colors hover:text-blue-600 xl:px-3">Dashboard</a>
                        @else
                            <a href="{{ route('dashboard') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-900 transition-colors hover:text-blue-600 xl:px-3">Dashboard</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher())
                            <a href="{{ route('projects.index') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">Projects</a>
                        @endif

                        @if(Auth::user()->canLeadGroup())
                            <a href="{{ route('advisers.title-submission') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">Find Advisers</a>
                        @elseif(Auth::user()->isTeacher())
                            <a href="{{ route('advisers.pending-requests') }}"
                                class="relative inline-flex items-center whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">
                                Student Requests
                                @if($studentRequestPendingCount > 0)
                                    <span class="ml-1.5 h-5 min-w-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                        {{ $studentRequestPendingCount > 99 ? '99+' : $studentRequestPendingCount }}
                                    </span>
                                @endif
                            </a>
                        @elseif(Auth::user()->role === 'Admin')
                            <a href="{{ route('admin.pending-users') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">Verify Users</a>
                            <a href="{{ route('admin.all-users') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">User Management</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher() || Auth::user()->isAdmin())
                            <a href="{{ route('defense-schedule.index') }}"
                                class="whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">Meeting Schedule</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher())
                            <a href="{{ route('chat.index') }}"
                                class="relative inline-flex items-center whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">
                                Chat
                                @if($chatUnreadCount > 0)
                                    <span class="ml-1.5 h-5 min-w-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                        {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                    </span>
                                @endif
                            </a>
                        @endif

                        <a href="{{ route('notifications.index') }}"
                            class="relative inline-flex items-center whitespace-nowrap px-2 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-blue-600 xl:px-3">
                            Notifications
                            @if($notificationUnreadCount > 0)
                                <span class="ml-1.5 h-5 min-w-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @endauth
                </nav>

                <!-- User Menu -->
                <div class="flex min-w-0 items-center space-x-4">
                    <!-- User Dropdown -->
                    @auth
                    <div class="relative min-w-0" x-data="{ open: false }">
                        <button @click="open = !open" class="flex min-w-0 max-w-44 items-center gap-2 text-gray-700 hover:text-blue-600 focus:outline-none">
                            @if(Auth::user()->profile_picture_path)
                                <img src="{{ route('profile.picture', Auth::user()) }}?v={{ Auth::user()->updated_at?->timestamp }}"
                                     alt="{{ Auth::user()->name }}"
                                     class="h-8 w-8 shrink-0 rounded-full border border-gray-200 object-cover">
                            @else
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                    {{ strtoupper(substr(Auth::user()->firstname, 0, 1) . substr(Auth::user()->lastname, 0, 1)) }}
                                </div>
                            @endif
                            <span class="hidden min-w-0 max-w-28 truncate text-left text-sm font-medium md:block">
                                {{ Auth::user()->name }}
                            </span>
                            <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher() || Auth::user()->canLeadGroup())
                                <a href="{{ route('setup-google-auth') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Connect Google Calendar</a>
                            @endif
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout', [], false) }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                    @endauth

                    <!-- Mobile menu button -->
                    @auth
                    <div class="xl:hidden">
                        <button type="button" @click="mobileOpen = !mobileOpen"
                            class="text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                    @endauth
                </div>
            </div>

            <nav x-show="mobileOpen" x-transition class="xl:hidden border-t border-gray-100 py-3 space-y-1">
                @auth
                    @if(Auth::user()->role === 'Admin')
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('admin.pending-users') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Verify Users</a>
                        <a href="{{ route('admin.all-users') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">User Management</a>
                        <a href="{{ route('defense-schedule.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Meeting Schedule</a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @elseif(Auth::user()->role === 'Teacher')
                        <a href="{{ route('teacher.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('projects.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Projects</a>
                        <a href="{{ route('advisers.pending-requests') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Student Requests</span>
                            @if($studentRequestPendingCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $studentRequestPendingCount > 99 ? '99+' : $studentRequestPendingCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('defense-schedule.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Meeting Schedule</a>
                        <a href="{{ route('chat.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Chat</span>
                            @if($chatUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('projects.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Projects</a>
                        @if(Auth::user()->canLeadGroup())
                            <a href="{{ route('advisers.title-submission') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Find Advisers</a>
                        @endif
                        <a href="{{ route('defense-schedule.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Meeting Schedule</a>
                        <a href="{{ route('chat.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Chat</span>
                            @if($chatUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>
