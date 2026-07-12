<div class="chat-room-item p-4 border-b border-gray-100 hover:bg-blue-50 cursor-pointer transition-colors {{ $room->is_pinned ? 'bg-yellow-50/60' : '' }}"
     data-room-id="{{ $room->id }}"
     data-room-pinned="{{ $room->is_pinned ? 'true' : 'false' }}"
     data-unread-count="{{ (int) ($room->unread_count ?? 0) }}"
     onclick="selectChatRoom({{ $room->id }})">
    <div class="flex items-center justify-between gap-3">
        <div class="flex-1 min-w-0">
            <div class="flex min-w-0 items-center gap-2">
                <h3 class="min-w-0 truncate text-sm font-medium text-gray-900">{{ $room->name }}</h3>
                <span class="chat-room-unread-count shrink-0 rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold leading-5 text-white {{ ($room->unread_count ?? 0) > 0 ? '' : 'hidden' }}"
                      data-unread-count="{{ (int) ($room->unread_count ?? 0) }}"
                      title="Unread messages"
                      aria-label="{{ (int) ($room->unread_count ?? 0) }} unread messages">
                    {{ ($room->unread_count ?? 0) > 99 ? '99+' : (int) ($room->unread_count ?? 0) }}
                </span>
                @if($room->is_archived ?? false)
                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">Archived</span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-1">
                @if($room->type === 'project' && $room->project)
                    Project: {{ $room->project->title }}
                @else
                    {{ ucfirst($room->type) }} Chat
                @endif
            </p>
            @if($room->latestMessage->first())
                <p class="text-xs text-gray-400 mt-1 truncate">
                    {{ $room->latestMessage->first()->user?->firstname ?? 'User' }}:
                    {{ Str::limit($room->latestMessage->first()->message, 30) }}
                </p>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @unless($room->is_archived ?? false)
                <button type="button"
                        onclick="toggleChatRoomPin({{ $room->id }}, event)"
                        class="rounded-md p-1.5 {{ $room->is_pinned ? 'text-yellow-600 hover:bg-yellow-100' : 'text-gray-400 hover:bg-gray-100 hover:text-yellow-600' }}"
                        title="{{ $room->is_pinned ? 'Unpin chat room' : 'Pin chat room' }}">
                    <svg class="h-4 w-4 {{ $room->is_pinned ? 'fill-current' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4l5 5-4 4v5l-2 2-5-5-4 4-1-1 4-4-5-5 2-2h5l4-4z"></path>
                    </svg>
                </button>
            @endunless
        </div>
    </div>
</div>
