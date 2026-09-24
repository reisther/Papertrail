<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    @php
        $sections = collect();

        if(Auth::user()->isAdmin()) {
            $sections->push([
                'key' => 'sign-ups',
                'type' => 'admin_signup',
                'title' => 'Sign ups',
                'description' => 'Account verification requests from new users.',
                'items' => $adminNotifications ?? collect(),
                'empty' => 'No pending sign-up notifications.',
                'color' => 'amber',
            ]);
        } else {
            $sections->push([
                'key' => 'chat',
                'type' => 'chat_mention',
                'title' => 'Chat',
                'description' => 'Only messages where someone mentioned you appear here.',
                'items' => $chatNotifications,
                'empty' => 'No chat mentions.',
                'color' => 'blue',
            ]);

            $sections->push([
                'key' => 'announcements',
                'type' => 'announcement',
                'title' => 'Announcements',
                'description' => 'Announcements posted by admins, advisers, and group leaders.',
                'items' => $announcementNotifications ?? collect(),
                'empty' => 'No announcement notifications.',
                'color' => 'blue',
            ]);

            if(Auth::user()->isTeacher()) {
                $sections->push([
                    'key' => 'student-requests',
                    'type' => 'student_request',
                    'title' => 'Student Requests',
                    'description' => 'Adviser requests sent by student groups.',
                    'items' => $studentRequestNotifications,
                    'empty' => 'No student request notifications.',
                    'color' => 'amber',
                ]);
            }

            $sections->push([
                'key' => 'meeting-schedule',
                'type' => 'meeting_schedule',
                'title' => 'Meeting Schedule',
                'description' => 'Meeting and consultation schedule updates.',
                'items' => $meetingNotifications,
                'empty' => 'No meeting schedule notifications.',
                'color' => 'green',
            ]);
        }

        $requestedSection = request('type');
        $activeSection = $sections->firstWhere('key', $requestedSection) ?? $sections->first();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if($sections->isNotEmpty())
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($sections as $section)
                                @php
                                    $isActive = $activeSection && $activeSection['key'] === $section['key'];
                                    $sectionUnreadCount = $section['items']->whereNull('read_at')->count();
                                @endphp
                                <a href="{{ route('notifications.index', ['type' => $section['key']]) }}"
                                   class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition
                                        {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    <span>{{ $section['title'] }}</span>
                                    @if($sectionUnreadCount > 0)
                                        <span class="h-5 min-w-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white" aria-label="{{ $sectionUnreadCount }} unread {{ strtolower($section['title']) }} notifications">
                                            {{ $sectionUnreadCount > 99 ? '99+' : $sectionUnreadCount }}
                                        </span>
                                    @else
                                        <span class="h-5 min-w-5 rounded-full px-1.5 text-center text-[11px] font-semibold leading-5 {{ $isActive ? 'bg-white/20 text-white' : 'bg-white text-gray-500' }}" aria-label="No unread {{ strtolower($section['title']) }} notifications">
                                            0
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if($activeSection)
                        <section>
                            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $activeSection['title'] }}</h3>
                                    <p class="text-sm text-gray-500">{{ $activeSection['description'] }}</p>
                                </div>
                                @php($unreadCount = $activeSection['items']->whereNull('read_at')->count())
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex w-fit items-center rounded-full {{ $unreadCount > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }} px-3 py-1 text-xs font-semibold">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }} unread
                                    </span>
                                    <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        {{ $activeSection['items']->count() }} total
                                    </span>
                                    @if($activeSection['items']->isNotEmpty())
                                        <form method="POST" action="{{ route('notifications.sections.read', $activeSection['type']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    @disabled($unreadCount === 0)
                                                    class="rounded-md px-3 py-2 text-xs font-semibold transition {{ $unreadCount > 0 ? 'bg-blue-600 text-white hover:bg-blue-700' : 'cursor-not-allowed bg-gray-100 text-gray-400' }}">
                                                {{ $unreadCount > 0 ? 'Mark all read' : 'All read' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if($activeSection['items']->isNotEmpty())
                                <div class="divide-y divide-gray-100">
                                    @foreach($activeSection['items'] as $notification)
                                        <div class="{{ $notification->read_at ? 'bg-white' : 'bg-blue-50/60' }} px-5 py-4">
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <a href="{{ route('notifications.open', $notification) }}" class="min-w-0 flex-1">
                                                    <div class="flex items-start gap-4">
                                                        <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                                                            @if($activeSection['color'] === 'amber') bg-amber-100 text-amber-700
                                                            @elseif($activeSection['color'] === 'green') bg-green-100 text-green-700
                                                            @else bg-blue-100 text-blue-700
                                                            @endif">
                                                            @if($activeSection['key'] === 'chat')
                                                                <img src="{{ asset('images/icons/chat-message.svg') }}" alt="" class="h-5 w-5 opacity-70">
                                                            @elseif($activeSection['key'] === 'announcements')
                                                                <img src="{{ asset('images/icons/announcement.svg') }}" alt="" class="h-5 w-5 opacity-70">
                                                            @elseif($activeSection['key'] === 'student-requests' || $activeSection['key'] === 'sign-ups')
                                                                <img src="{{ asset('images/icons/people-add.svg') }}" alt="" class="h-5 w-5 opacity-70">
                                                            @else
                                                                <img src="{{ asset('images/icons/meeting-calendar.svg') }}" alt="" class="h-5 w-5 opacity-70">
                                                            @endif
                                                        </div>

                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <p class="font-semibold text-gray-900">{{ $notification->title }}</p>
                                                                @if(!$notification->read_at)
                                                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Unread</span>
                                                                @endif
                                                            </div>
                                                            @if($notification->body)
                                                                <p class="mt-1 text-sm text-gray-600">{{ $notification->body }}</p>
                                                            @endif
                                                            <p class="mt-2 text-xs text-gray-400">{{ $notification->created_at?->diffForHumans() }}</p>
                                                        </div>
                                                    </div>
                                                </a>

                                                <form method="POST" action="{{ $notification->read_at ? route('notifications.unread', $notification) : route('notifications.read', $notification) }}" class="shrink-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:w-28">
                                                        {{ $notification->read_at ? 'Mark unread' : 'Mark read' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-5 py-8 text-center text-sm text-gray-500">
                                    {{ $activeSection['empty'] }}
                                </div>
                            @endif
                        </section>
                    @endif
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-white p-10 text-center shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-900">No notification sections</h3>
                    <p class="mt-2 text-gray-600">Notifications are not available for this role.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
