@php
    $announcementUser = auth()->user();
    $canManageAnnouncements = $announcementUser?->isAdmin() || $announcementUser?->isTeacher() || $announcementUser?->canLeadGroup();
    $visibleAnnouncements = \Illuminate\Support\Facades\Schema::hasTable('announcements')
        ? \App\Models\Announcement::with('author')
            ->visibleTo($announcementUser)
            ->latest()
            ->get()
        : collect();

    $announcementGroups = [
        'admin' => [
            'title' => 'ADMIN ANNOUNCEMENT',
            'items' => $visibleAnnouncements->where('audience_type', 'global')->values(),
            'tone' => [
                'border' => 'border-red-300',
                'badge' => 'bg-red-100 text-red-800',
                'label' => 'text-red-700',
                'button' => 'bg-red-100 text-red-800 hover:bg-red-200',
                'header' => 'border-red-100 bg-red-50',
                'source' => 'Admin announcement',
            ],
        ],
        'adviser' => [
            'title' => 'ADVISER ANNOUNCEMENT',
            'items' => $visibleAnnouncements->where('audience_type', 'adviser_students')->values(),
            'tone' => [
                'border' => 'border-green-300',
                'badge' => 'bg-green-100 text-green-800',
                'label' => 'text-green-700',
                'button' => 'bg-green-100 text-green-800 hover:bg-green-200',
                'header' => 'border-green-100 bg-green-50',
                'source' => 'Adviser announcement',
            ],
        ],
        'leader' => [
            'title' => 'LEADER ANNOUNCEMENT',
            'items' => $visibleAnnouncements->where('audience_type', 'project')->values(),
            'tone' => [
                'border' => 'border-blue-300',
                'badge' => 'bg-blue-100 text-blue-800',
                'label' => 'text-blue-700',
                'button' => 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                'header' => 'border-blue-100 bg-blue-50',
                'source' => 'Leader announcement',
            ],
        ],
    ];

    $hasAnnouncements = $visibleAnnouncements->isNotEmpty();
@endphp

<div class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm" x-data="{ openAnnouncement: null, showAnnouncementGroup: null }">
    <div class="border-b border-amber-200 bg-white/70 px-5 py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="text-lg font-extrabold text-amber-950">Announcements</h4>
                    <p class="text-sm font-medium text-amber-800">Latest important updates for you.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($canManageAnnouncements)
                    <a href="{{ $announcementUser?->isAdmin() ? route('admin.announcements') : route('announcements.manage') }}" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700">
                        Post an Announcement
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-5 p-5">
        @foreach($announcementGroups as $groupKey => $group)
            @if($group['items']->isNotEmpty())
                @php
                    $announcement = $group['items']->first();
                    $announcementTone = $group['tone'];
                @endphp
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h5 class="text-sm font-extrabold uppercase {{ $announcementTone['label'] }}">{{ $group['title'] }}</h5>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" @click="showAnnouncementGroup = '{{ $groupKey }}'" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-800">
                                Show all previous
                            </button>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $announcementTone['badge'] }}">Latest</span>
                        </div>
                    </div>
                    <div class="rounded-xl border-2 {{ $announcementTone['border'] }} bg-white p-4 shadow-sm">
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-950">{{ $announcement->author?->name ?? 'Admin' }}</p>
                                <p class="text-xs font-semibold uppercase {{ $announcementTone['label'] }}">{{ $announcementTone['source'] }}</p>
                            </div>
                            <p class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $announcementTone['badge'] }}">{{ $announcement->created_at->diffForHumans() }}</p>
                        </div>
                        <p class="whitespace-pre-line text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($announcement->message, 180) }}</p>
                        @if($announcement->attachment_path)
                            <a href="{{ route('announcements.attachment', $announcement) }}" class="mt-3 inline-flex max-w-full items-center gap-2 truncate rounded-md px-3 py-1.5 text-xs font-bold {{ $announcementTone['button'] }}">
                                {{ $announcement->attachment_name ?? 'Attachment' }}
                            </a>
                        @endif
                        <div class="mt-3">
                            <button type="button" @click="openAnnouncement = {{ $announcement->id }}" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-800">
                                Show more
                            </button>
                        </div>
                    </div>

                    <div x-show="openAnnouncement === {{ $announcement->id }}" x-transition style="display: none;" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/90 px-4 py-6">
                        <div class="absolute inset-0" @click="openAnnouncement = null"></div>
                        <div class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                            <div class="flex items-center justify-between gap-4 border-b px-5 py-4 {{ $announcementTone['header'] }}">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase {{ $announcementTone['label'] }}">{{ $announcementTone['source'] }}</p>
                                    <h3 class="truncate text-lg font-extrabold text-gray-950">{{ $announcement->author?->name ?? 'Admin' }}</h3>
                                </div>
                                <p class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold {{ $announcementTone['label'] }}">{{ $announcement->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="max-h-[55vh] overflow-y-auto px-5 py-5">
                                <p class="whitespace-pre-line text-sm leading-6 text-gray-800">{{ $announcement->message }}</p>
                                @if($announcement->attachment_path)
                                    <a href="{{ route('announcements.attachment', $announcement) }}" class="mt-5 inline-flex max-w-full items-center gap-2 truncate rounded-md px-3 py-2 text-xs font-bold {{ $announcementTone['button'] }}">
                                        {{ $announcement->attachment_name ?? 'Attachment' }}
                                    </a>
                                @endif
                            </div>
                            <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-4">
                                <button type="button" @click="openAnnouncement = null" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-bold text-white hover:bg-gray-800">
                                    Back
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="showAnnouncementGroup === '{{ $groupKey }}'" x-transition style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4 py-6">
                        <div class="absolute inset-0" @click="showAnnouncementGroup = null"></div>
                        <div class="relative z-10 flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                            <div class="flex items-center justify-between gap-4 border-b px-5 py-4 {{ $announcementTone['header'] }}">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase {{ $announcementTone['label'] }}">Announcements</p>
                                    <h3 class="text-xl font-extrabold text-gray-950">{{ $group['title'] }}</h3>
                                </div>
                                <button type="button" @click="showAnnouncementGroup = null" class="shrink-0 rounded-lg bg-gray-900 px-4 py-2 text-sm font-bold text-white hover:bg-gray-800">
                                    Back
                                </button>
                            </div>

                            <div class="space-y-3 overflow-y-auto px-5 py-5">
                                @foreach($group['items'] as $announcement)
                                    <div class="rounded-xl border-2 {{ $announcementTone['border'] }} bg-white p-4 shadow-sm">
                                        <div class="mb-2 flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-950">{{ $announcement->author?->name ?? 'Admin' }}</p>
                                                <p class="text-xs font-semibold uppercase {{ $announcementTone['label'] }}">{{ $announcementTone['source'] }}</p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <button type="button" @click="openAnnouncement = {{ $announcement->id }}" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-800">
                                                    Expand
                                                </button>
                                                <p class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $announcementTone['badge'] }}">{{ $announcement->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        <p class="whitespace-pre-line text-sm leading-6 text-gray-800">{{ \Illuminate\Support\Str::limit($announcement->message, 220) }}</p>
                                        @if($announcement->attachment_path)
                                            <a href="{{ route('announcements.attachment', $announcement) }}" class="mt-3 inline-flex max-w-full items-center gap-2 truncate rounded-md px-3 py-2 text-xs font-bold {{ $announcementTone['button'] }}">
                                                {{ $announcement->attachment_name ?? 'Attachment' }}
                                            </a>
                                        @endif

                                        <div x-show="openAnnouncement === {{ $announcement->id }}" x-transition style="display: none;" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/90 px-4 py-6">
                                            <div class="absolute inset-0" @click="openAnnouncement = null"></div>
                                            <div class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                                                <div class="flex items-center justify-between gap-4 border-b px-5 py-4 {{ $announcementTone['header'] }}">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-bold uppercase {{ $announcementTone['label'] }}">{{ $announcementTone['source'] }}</p>
                                                        <h3 class="truncate text-lg font-extrabold text-gray-950">{{ $announcement->author?->name ?? 'Admin' }}</h3>
                                                    </div>
                                                    <p class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold {{ $announcementTone['label'] }}">{{ $announcement->created_at->diffForHumans() }}</p>
                                                </div>
                                                <div class="max-h-[55vh] overflow-y-auto px-5 py-5">
                                                    <p class="whitespace-pre-line text-sm leading-6 text-gray-800">{{ $announcement->message }}</p>
                                                    @if($announcement->attachment_path)
                                                        <a href="{{ route('announcements.attachment', $announcement) }}" class="mt-5 inline-flex max-w-full items-center gap-2 truncate rounded-md px-3 py-2 text-xs font-bold {{ $announcementTone['button'] }}">
                                                            {{ $announcement->attachment_name ?? 'Attachment' }}
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-4">
                                                    <button type="button" @click="openAnnouncement = null" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-bold text-white hover:bg-gray-800">
                                                        Back
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        @if(!$hasAnnouncements)
            <div class="rounded-xl border border-dashed border-amber-300 bg-white/70 p-4 text-sm font-medium text-amber-800">
                No announcements yet.
            </div>
        @endif
    </div>

</div>
