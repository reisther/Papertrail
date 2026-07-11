<x-app-layout>
    @php
        $memberWithoutGroup = Auth::user()->isStudent() && Auth::user()->joinedProjects()->doesntExist();
        $leaderWithoutGroup = Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist();
        $isArchivedChatsPage = $isArchivedChatsPage ?? false;
        $showArchivedChatsLink = $isArchivedChatsPage || Auth::user()->isTeacher();
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isArchivedChatsPage ? __('Archived Chats') : __('Chat System') }}
        </h2>
    </x-slot>
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $isArchivedChatsPage ? 'Archived Chats' : 'Chat & Collaboration' }}</h1>
                <p class="mt-2 text-gray-600">{{ $isArchivedChatsPage ? 'Review saved adviser chat history after a group is archived.' : 'Communicate with your project team and advisers in real-time' }}</p>
            </div>
            @if($showArchivedChatsLink)
                <a href="{{ $isArchivedChatsPage ? route('chat.index') : route('chat.archived') }}"
                   class="inline-flex h-10 items-center justify-center rounded-md {{ $isArchivedChatsPage ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-800 hover:bg-gray-900 text-white' }} px-4 py-2 text-sm font-medium transition-colors">
                    {{ $isArchivedChatsPage ? 'Active Chats' : 'Archived Chats' }}
                </a>
            @endif
        </div>

        @if($leaderWithoutGroup && ! $isArchivedChatsPage)
            @include('partials.leader-create-group-card')
        @else
        @php
            $chatRoomGroups = $chatRooms->groupBy(fn ($room) => $room->project?->title ?? 'General Rooms');
            $showFolderSidebar = Auth::user()->isTeacher();
            $canCreateChatRooms = ! $isArchivedChatsPage && (Auth::user()->canLeadGroup() || Auth::user()->isTeacher());
        @endphp
        <div id="chatLayout" class="grid grid-cols-1 lg:grid-cols-4 gap-4 lg:gap-6 lg:h-[calc(100vh-12rem)]">
            <!-- Chat Rooms Sidebar -->
            <div id="chatSidebar" class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden max-h-[calc(100vh-9rem)] lg:max-h-none">
                <div class="sticky top-0 z-10 p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $isArchivedChatsPage ? 'Archived Chats' : 'Chat Rooms' }}</h2>
                        @if($canCreateChatRooms)
                            <button onclick="openCreateRoomModal()" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="overflow-y-auto h-full" id="chatRoomsList">
                    @if($chatRooms->isNotEmpty() && $showFolderSidebar)
                        <div class="divide-y divide-gray-100">
                            @foreach($chatRoomGroups as $projectName => $rooms)
                                @php($folderUnreadCount = $rooms->sum(fn ($room) => $room->unread_count ?? 0))
                                <details class="chat-room-folder">
                                    <summary class="flex items-center justify-between gap-3 bg-blue-50/70 p-4 cursor-pointer hover:bg-blue-100/70">
                                        <span class="min-w-0 inline-flex items-center gap-2 text-sm font-semibold text-blue-800">
                                            <svg class="h-4 w-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                                            </svg>
                                            <span class="truncate">{{ $projectName }}</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            @if($folderUnreadCount > 0)
                                                <span class="chat-folder-unread-count text-xs rounded-full bg-red-600 px-2 py-1 font-semibold text-white" data-unread-count="{{ $folderUnreadCount }}" title="Unread chats">
                                                    {{ $folderUnreadCount > 99 ? '99+' : $folderUnreadCount }}
                                                </span>
                                            @endif
                                            <span class="text-xs rounded-full bg-blue-100 text-blue-700 px-2 py-1" title="Chat rooms">
                                                {{ $rooms->count() }}
                                            </span>
                                        </span>
                                    </summary>

                                    <div class="pb-2">
                                        @foreach($rooms as $room)
                                            @include('chat.partials.room-item', ['room' => $room])
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @elseif($chatRooms->isNotEmpty())
                        @foreach($chatRooms as $room)
                            @include('chat.partials.room-item', ['room' => $room])
                        @endforeach
                    @else
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            @if($memberWithoutGroup)
                                <p class="text-sm">You're not in a group chat yet</p>
                                <p class="text-xs mt-1">Ask your leader to send you the invitation link for your group. Once you join, your group chat will appear here.</p>
                            @else
                                <p class="text-sm">{{ $isArchivedChatsPage ? 'No archived chats yet' : 'No chat rooms yet' }}</p>
                                <p class="text-xs mt-1">{{ $isArchivedChatsPage ? 'Archived adviser chats will appear here after a group is archived.' : 'Chat rooms appear here after your group leader creates one' }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Chat Interface -->
            <div id="chatPanel" class="relative hidden lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200 overflow-visible lg:overflow-hidden flex-col min-h-[calc(100vh-9rem)] lg:min-h-0 lg:flex">
                <div id="chatHeader" class="sticky top-0 z-40 p-3 sm:p-4 border-b border-gray-200 bg-gray-50 shadow-sm lg:shadow-none hidden">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" onclick="showChatRoomsView()" class="lg:hidden inline-flex shrink-0 items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" title="Back to chat rooms">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Rooms</span>
                        </button>
                        <div class="min-w-0 flex-1">
                            <h3 id="chatRoomName" class="text-lg font-semibold text-gray-900"></h3>
                            <p id="chatRoomDescription" class="text-sm text-gray-600"></p>
                            <p id="archivedChatNotice" class="mt-1 hidden text-sm font-medium text-gray-600">Archived chats: this conversation is saved as read-only history.</p>
                        </div>
                        <div class="flex shrink-0 items-center space-x-1 sm:space-x-2">
                            <button id="pinnedMessagesButton" onclick="togglePinnedMessagesPanel()" class="relative text-gray-400 hover:text-yellow-700 p-2 rounded-lg hover:bg-yellow-50" title="View Pinned Messages">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4l5 5-4 4v5l-2 2-5-5-4 4-1-1 4-4-5-5 2-2h5l4-4z"></path>
                                </svg>
                                <span id="pinnedMessagesCount" class="hidden absolute -right-1 -top-1 min-w-5 h-5 rounded-full bg-yellow-500 px-1 text-center text-[11px] font-semibold leading-5 text-white"></span>
                            </button>
                            <button onclick="showParticipants()" class="text-gray-400 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50" title="View Participants">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h11"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h11"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17h11"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h.01"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h.01"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17h.01"></path>
                                </svg>
                            </button>
                            <button id="leaveChatRoomBtn" onclick="leaveChatRoom()" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50" title="Leave Chat Room">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                            <button id="deleteChatRoomBtn" onclick="deleteChatRoom()" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 hidden" title="Delete Chat Room">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Messages Area -->
                <div id="pinnedMessagesPanel" class="fixed left-3 right-3 z-30 hidden max-h-[min(60vh,22rem)] overflow-y-auto rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-3 shadow-xl sm:left-4 sm:right-4 sm:px-4 lg:absolute lg:left-3 lg:right-3"></div>

                <div id="messagesContainer" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-4 hidden">
                    <!-- Messages will be loaded here -->
                </div>
                <button id="scrollToBottomButton" type="button" onclick="scrollMessagesToBottom(true)" class="hidden absolute bottom-24 right-5 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" title="Scroll to latest message">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Typing Indicator -->
                <div id="typingIndicator" class="px-4 py-2 text-sm text-gray-500 italic hidden">
                    <!-- Typing status will be shown here -->
                </div>

                <!-- Welcome Message -->
                <div id="welcomeMessage" class="flex-1 flex items-center justify-center text-center p-8">
                    <div>
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        @if($memberWithoutGroup && $chatRooms->isEmpty())
                            <h3 class="text-lg font-medium text-gray-900 mb-2">You're not in a group yet</h3>
                            <p class="text-gray-600 max-w-md">Ask your leader to send you the invitation link for your group. After you join, you can send chats and files here.</p>
                        @else
                            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $isArchivedChatsPage ? 'Archived Chat History' : 'Welcome to Paper Trail Chat' }}</h3>
                            <p class="text-gray-600 max-w-md">{{ $isArchivedChatsPage ? 'Select an archived chat room from the sidebar to review the saved read-only conversation.' : 'Select a chat room from the sidebar to start collaborating with your team members and advisers.' }}</p>
                        @endif
                    </div>
                </div>

                <!-- Message Input -->
                <div id="messageInput" class="p-3 sm:p-4 border-t border-gray-200 hidden">
                    <div id="replyPreview" class="hidden mb-3 rounded-md border-l-4 border-blue-500 bg-blue-50 px-3 py-2 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-blue-800">Replying to <span id="replyPreviewUser"></span></p>
                                <p id="replyPreviewText" class="truncate text-blue-700"></p>
                            </div>
                            <button type="button" onclick="clearReply()" class="shrink-0 text-blue-500 hover:text-blue-700" title="Cancel reply">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="editPreview" class="hidden mb-3 rounded-md border-l-4 border-amber-500 bg-amber-50 px-3 py-2 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-amber-800">Editing message</p>
                                <p id="editPreviewText" class="truncate text-amber-700"></p>
                            </div>
                            <button type="button" onclick="clearEdit()" class="shrink-0 text-amber-500 hover:text-amber-700" title="Cancel edit">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <form id="sendMessageForm" class="flex min-w-0 items-end gap-2 sm:gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-end gap-2">
                                <button type="button" onclick="toggleFileUpload()" class="shrink-0 text-gray-400 hover:text-gray-600 p-2" title="Attach file">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                </button>
                                <div class="relative min-w-0 flex-1">
                                    <textarea id="messageText" name="message" rows="1" 
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 resize-none"
                                              placeholder="Type your message..." 
                                              onkeydown="handleMessageKeydown(event)"></textarea>
                                    <div id="mentionSuggestions" class="absolute bottom-full left-0 z-50 mb-2 hidden max-h-52 w-72 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"></div>
                                    <input type="file" id="fileInput" name="file" class="hidden" onchange="handleFileSelect(event)">
                                </div>
                            </div>
                            <div id="filePreview" class="hidden mt-2 max-w-full overflow-hidden rounded border bg-gray-50 p-2 text-sm">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span id="fileName" class="min-w-0 flex-1 truncate text-gray-700"></span>
                                    <button type="button" onclick="clearFileSelection()" class="shrink-0 text-red-500 hover:text-red-700" title="Remove attachment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-md transition-colors" title="Send message">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    @media (max-width: 1023px) {
        body.chat-mobile-open {
            overflow: hidden;
        }

        #chatPanel.chat-mobile-active {
            position: fixed;
            inset: 4rem 0 0 0;
            z-index: 45;
            display: flex;
            min-height: 0;
            overflow: hidden;
            border-right: 0;
            border-bottom: 0;
            border-left: 0;
            border-radius: 0;
        }

        #chatPanel.chat-mobile-active #chatHeader,
        #chatPanel.chat-mobile-active #messageInput,
        #chatPanel.chat-mobile-active #typingIndicator {
            flex-shrink: 0;
        }

        #chatPanel.chat-mobile-active #chatHeader {
            position: relative;
            top: auto;
        }

        #chatPanel.chat-mobile-active #messagesContainer {
            flex: 1 1 auto;
            min-height: 0;
            overscroll-behavior: contain;
        }
    }
</style>

<!-- Create Room Modal -->
<div id="createRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Create Chat Room</h3>
                <button onclick="closeCreateRoomModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="createRoomForm" method="POST" action="{{ route('chat.rooms.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="roomName" class="block text-sm font-medium text-gray-700">Room Name</label>
                        <input type="text" id="roomName" name="name" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="roomDescription" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="roomDescription" name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label for="roomType" class="block text-sm font-medium text-gray-700">Type</label>
                        <select id="roomType" name="type" required 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="project">Project Chat</option>
                            <option value="group">Group Chat</option>
                        </select>
                    </div>
                    @if($availableProjects->isNotEmpty())
                        <div>
                            <label for="projectId" class="block text-sm font-medium text-gray-700">Group</label>
                            <select id="projectId" name="project_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($availableProjects as $project)
                                    <option value="{{ $project->id }}">{{ $project->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif(Auth::user()->canLeadGroup() || Auth::user()->isTeacher())
                        <p class="text-sm text-gray-500">This room will be created in your active group.</p>
                    @endif
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateRoomModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="createRoomSubmit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                        Create Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Participants Modal -->
<div id="addParticipantsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-[60]">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Participants</h3>
                <button onclick="closeAddParticipantsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="addParticipantsForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Users to Add</label>
                        <div id="availableUsersList" class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2">
                            <!-- Available users will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddParticipantsModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                        Add Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Participants List Modal -->
<div id="participantsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Participants</h3>
                <div class="flex items-center gap-2">
                    <button id="participantsAddButton" onclick="showAddParticipantsModal()" class="hidden rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">
                        Add
                    </button>
                    <button onclick="closeParticipantsModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div id="participantsList" class="space-y-2">
                <!-- Participants will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="notificationTitle" class="text-lg font-medium text-gray-900">Notification</h3>
                <button onclick="closeNotificationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="notificationMessage" class="mb-4 text-gray-600">
                <!-- Message content -->
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeNotificationModal()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="confirmationTitle" class="text-lg font-medium text-gray-900">Confirm Action</h3>
                <button onclick="closeConfirmationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="confirmationMessage" class="mb-4 text-gray-600">
                <!-- Message content -->
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeConfirmationModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button id="confirmationButton" onclick="handleConfirmation()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Emoji Picker Modal -->
<div id="emojiPickerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-80 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Reaction</h3>
                <button onclick="closeEmojiPicker()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-8 gap-2 mb-4">
                <!-- Common emoji reactions -->
                <button onclick="addReaction('👍')" class="p-2 text-2xl hover:bg-gray-100 rounded">👍</button>
                <button onclick="addReaction('❤️')" class="p-2 text-2xl hover:bg-gray-100 rounded">❤️</button>
                <button onclick="addReaction('😂')" class="p-2 text-2xl hover:bg-gray-100 rounded">😂</button>
                <button onclick="addReaction('😮')" class="p-2 text-2xl hover:bg-gray-100 rounded">😮</button>
                <button onclick="addReaction('😢')" class="p-2 text-2xl hover:bg-gray-100 rounded">😢</button>
                <button onclick="addReaction('😡')" class="p-2 text-2xl hover:bg-gray-100 rounded">😡</button>
                <button onclick="addReaction('👏')" class="p-2 text-2xl hover:bg-gray-100 rounded">👏</button>
                <button onclick="addReaction('🔥')" class="p-2 text-2xl hover:bg-gray-100 rounded">🔥</button>
                <button onclick="addReaction('✅')" class="p-2 text-2xl hover:bg-gray-100 rounded">✅</button>
                <button onclick="addReaction('❌')" class="p-2 text-2xl hover:bg-gray-100 rounded">❌</button>
                <button onclick="addReaction('🎉')" class="p-2 text-2xl hover:bg-gray-100 rounded">🎉</button>
                <button onclick="addReaction('💯')" class="p-2 text-2xl hover:bg-gray-100 rounded">💯</button>
                <button onclick="addReaction('🤔')" class="p-2 text-2xl hover:bg-gray-100 rounded">🤔</button>
                <button onclick="addReaction('👀')" class="p-2 text-2xl hover:bg-gray-100 rounded">👀</button>
                <button onclick="addReaction('💡')" class="p-2 text-2xl hover:bg-gray-100 rounded">💡</button>
                <button onclick="addReaction('⚡')" class="p-2 text-2xl hover:bg-gray-100 rounded">⚡</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Message Options Modal -->
<div id="deleteMessageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Delete Message</h3>
                <button onclick="closeDeleteMessageModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4 text-gray-600">
                Choose how you want to delete this message:
            </div>
            <div class="space-y-3" id="deleteMessageButtons">
                <button onclick="performDeleteMessage('self')" 
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    Delete for me only
                </button>
                <button onclick="performDeleteMessage('everyone')" 
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                    Delete for everyone
                </button>
                <button onclick="closeDeleteMessageModal()" 
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRoomId = null;
let messagePollingInterval = null;
let confirmationCallback = null;
let isInitialLoad = false;
let shouldScrollToBottom = false;
let selectedReplyMessage = null;
let selectedEditMessage = null;
let currentMessagesById = {};
let currentPinnedMessages = [];
let currentChatRoomDetails = null;
let selectedMentionIds = new Set();
let mentionParticipantsLoadedForRoomId = null;
let mentionParticipantsLoading = false;

// Modal functions
function showNotification(title, message, type = 'info') {
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationMessage').innerHTML = message;
    
    const button = document.querySelector('#notificationModal button[onclick="closeNotificationModal()"]');
    if (type === 'error') {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md';
    } else if (type === 'success') {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md';
    } else {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md';
    }
    
    document.getElementById('notificationModal').classList.remove('hidden');
}

function closeNotificationModal() {
    document.getElementById('notificationModal').classList.add('hidden');
}

function showConfirmation(title, message, callback, confirmText = 'Confirm', confirmType = 'danger') {
    document.getElementById('confirmationTitle').textContent = title;
    document.getElementById('confirmationMessage').innerHTML = message;
    
    const confirmButton = document.getElementById('confirmationButton');
    confirmButton.textContent = confirmText;
    
    if (confirmType === 'danger') {
        confirmButton.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md';
    } else {
        confirmButton.className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md';
    }
    
    confirmationCallback = callback;
    document.getElementById('confirmationModal').classList.remove('hidden');
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    confirmationCallback = null;
}

function handleConfirmation() {
    if (confirmationCallback) {
        confirmationCallback();
    }
    closeConfirmationModal();
}

// Initialize chat interface
document.addEventListener('DOMContentLoaded', function() {
    const requestedRoomId = @json(request('room'));
    const requestedRoom = requestedRoomId
        ? document.querySelector(`.chat-room-item[data-room-id="${requestedRoomId}"]`)
        : null;

    if (requestedRoom) {
        selectChatRoom(requestedRoomId);
        return;
    }

    // Auto-select first room if available
    const firstRoom = document.querySelector('.chat-room-item');
    if (firstRoom && window.innerWidth >= 1024) {
        const roomId = firstRoom.dataset.roomId;
        selectChatRoom(roomId);
    }

    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.addEventListener('scroll', updateScrollToBottomButton);
    }

    window.addEventListener('resize', function() {
        syncChatResponsiveState();

        const pinnedPanel = document.getElementById('pinnedMessagesPanel');
        if (pinnedPanel && !pinnedPanel.classList.contains('hidden')) {
            positionPinnedMessagesPanel();
        }
    });
});

// Select and load a chat room
function selectChatRoom(roomId) {
    clearEdit();
    clearReply();
    clearFileSelection();
    currentRoomId = roomId;
    selectedMentionIds.clear();
    mentionParticipantsLoadedForRoomId = null;
    hideMentionSuggestions();
    
    // Update UI
    document.querySelectorAll('.chat-room-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
    });
    
    const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
    if (selectedRoom) {
        selectedRoom.classList.add('bg-blue-50', 'border-l-4', 'border-blue-500');
    }
    
    // Show chat interface
    document.getElementById('welcomeMessage').classList.add('hidden');
    document.getElementById('chatHeader').classList.remove('hidden');
    document.getElementById('messagesContainer').classList.remove('hidden');
    document.getElementById('messageInput').classList.remove('hidden');
    showChatPanelView();
    clearReply();
    renderPinnedMessages([]);
    
    // Set flag for initial load
    isInitialLoad = true;
    
    // Load room details and messages
    loadChatRoom(roomId);
    loadMessages(roomId);
    
    // Start polling for new messages
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    messagePollingInterval = setInterval(() => loadMessages(roomId), 3000);
    
    // Start typing indicator polling
    if (window.typingPollingInterval) {
        clearInterval(window.typingPollingInterval);
    }
    window.typingPollingInterval = setInterval(getTypingUsers, 2000); // Check every 2 seconds
}

function formatUnreadCount(count) {
    return count > 99 ? '99+' : String(count);
}

function decrementChatNavUnreadCount(unreadCount) {
    document.querySelectorAll('.chat-nav-unread-count').forEach(badge => {
        const currentCount = Number(badge.dataset.unreadCount || badge.textContent || 0);
        const nextCount = Math.max(0, currentCount - unreadCount);

        if (nextCount > 0) {
            badge.dataset.unreadCount = String(nextCount);
            badge.textContent = formatUnreadCount(nextCount);
        } else {
            badge.remove();
        }
    });
}

function clearChatRoomUnreadCount(roomId) {
    const roomItem = document.querySelector(`.chat-room-item[data-room-id="${roomId}"]`);
    if (!roomItem) return;

    const roomBadge = roomItem.querySelector('.chat-room-unread-count');
    const unreadCount = Number(roomBadge?.dataset.unreadCount || roomBadge?.textContent || 0);
    if (!roomBadge || unreadCount <= 0) return;

    roomBadge.remove();
    decrementChatNavUnreadCount(unreadCount);

    const folder = roomItem.closest('details.chat-room-folder');
    const folderBadge = folder?.querySelector('.chat-folder-unread-count');
    if (!folderBadge) return;

    const folderUnreadCount = Number(folderBadge.dataset.unreadCount || folderBadge.textContent || 0);
    const nextFolderUnreadCount = Math.max(0, folderUnreadCount - unreadCount);

    if (nextFolderUnreadCount > 0) {
        folderBadge.dataset.unreadCount = String(nextFolderUnreadCount);
        folderBadge.textContent = formatUnreadCount(nextFolderUnreadCount);
    } else {
        folderBadge.remove();
    }
}

function showChatPanelView() {
    const sidebar = document.getElementById('chatSidebar');
    const panel = document.getElementById('chatPanel');

    panel.classList.remove('hidden');
    panel.classList.add('flex');

    if (window.innerWidth < 1024) {
        sidebar.classList.add('hidden');
        panel.classList.add('chat-mobile-active');
        document.body.classList.add('chat-mobile-open');
    } else {
        panel.classList.remove('chat-mobile-active');
        document.body.classList.remove('chat-mobile-open');
    }
}

function showChatRoomsView() {
    const sidebar = document.getElementById('chatSidebar');
    const panel = document.getElementById('chatPanel');
    if (!sidebar || !panel) return;

    if (window.innerWidth < 1024) {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        panel.classList.remove('chat-mobile-active');
        sidebar.classList.remove('hidden');
        document.body.classList.remove('chat-mobile-open');
        closePinnedMessagesPanel();
    }

    sidebar.scrollIntoView({ behavior: 'smooth', block: 'start' });
    sidebar.classList.add('ring-2', 'ring-blue-200');

    setTimeout(() => {
        sidebar.classList.remove('ring-2', 'ring-blue-200');
    }, 1200);
}

function syncChatResponsiveState() {
    const sidebar = document.getElementById('chatSidebar');
    const panel = document.getElementById('chatPanel');
    if (!sidebar || !panel) return;

    const hasActiveRoom = Boolean(currentRoomId);

    if (window.innerWidth >= 1024) {
        sidebar.classList.remove('hidden');
        panel.classList.remove('chat-mobile-active');
        document.body.classList.remove('chat-mobile-open');

        if (hasActiveRoom) {
            panel.classList.remove('hidden');
            panel.classList.add('flex');
        }

        return;
    }

    if (hasActiveRoom && !panel.classList.contains('hidden')) {
        sidebar.classList.add('hidden');
        panel.classList.add('chat-mobile-active');
        document.body.classList.add('chat-mobile-open');
    } else {
        sidebar.classList.remove('hidden');
        panel.classList.remove('chat-mobile-active');
        document.body.classList.remove('chat-mobile-open');
    }
}

function stopChatPolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }

    if (window.typingPollingInterval) {
        clearInterval(window.typingPollingInterval);
        window.typingPollingInterval = null;
    }
}

function removeChatRoomFromList(roomId) {
    const roomItem = document.querySelector(`.chat-room-item[data-room-id="${roomId}"]`);
    if (!roomItem) return;

    const folder = roomItem.closest('details.chat-room-folder');
    roomItem.remove();

    if (folder) {
        const remainingRooms = folder.querySelectorAll('.chat-room-item').length;
        const countBadge = folder.querySelector('summary span:last-child');

        if (countBadge) {
            countBadge.textContent = remainingRooms;
        }

        if (remainingRooms === 0) {
            folder.remove();
        }
    }

    const chatRoomsList = document.getElementById('chatRoomsList');
    if (chatRoomsList && !chatRoomsList.querySelector('.chat-room-item')) {
        chatRoomsList.innerHTML = `
            <div class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="text-sm">No chat rooms yet</p>
                <p class="text-xs mt-1">Chat rooms appear here after your group leader creates one</p>
            </div>
        `;
    }
}

function clearActiveChatRoom(roomId = currentRoomId) {
    stopChatPolling();
    closePinnedMessagesPanel();
    closeParticipantsModal();
    closeAddParticipantsModal();
    clearEdit();
    clearReply();
    clearFileSelection();
    renderPinnedMessages([]);

    currentRoomId = null;
    currentChatRoomDetails = null;
    currentMessagesById = {};

    document.querySelectorAll('.chat-room-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
    });

    document.getElementById('chatHeader').classList.add('hidden');
    document.getElementById('messagesContainer').classList.add('hidden');
    document.getElementById('messagesContainer').innerHTML = '';
    document.getElementById('messageInput').classList.add('hidden');
    document.getElementById('typingIndicator').classList.add('hidden');
    document.getElementById('welcomeMessage').classList.remove('hidden');
    document.getElementById('messageText').value = '';

    if (window.innerWidth < 1024) {
        showChatRoomsView();
    }
}

function handleChatAccessLost(roomId, message = 'You are no longer in this chat room.') {
    if (String(roomId) !== String(currentRoomId)) return;

    removeChatRoomFromList(roomId);
    clearActiveChatRoom(roomId);
    showNotification('Chat Closed', message, 'info');
}

// Load chat room details
async function loadChatRoom(roomId) {
    try {
        const response = await fetch(`/chat/rooms/${roomId}`);
        const data = await response.json();
        
        if (data.chat_room) {
            currentChatRoomDetails = data.chat_room;
            document.getElementById('chatRoomName').textContent = data.chat_room.name;
            document.getElementById('chatRoomDescription').textContent = data.chat_room.description || '';
            const isArchived = Boolean(data.chat_room.is_archived);
            document.getElementById('archivedChatNotice')?.classList.toggle('hidden', !isArchived);
            document.getElementById('messageInput')?.classList.toggle('hidden', isArchived);
            document.getElementById('typingIndicator')?.classList.toggle('hidden', isArchived);
            
            const deleteBtn = document.getElementById('deleteChatRoomBtn');
            const leaveBtn = document.getElementById('leaveChatRoomBtn');
            leaveBtn?.classList.toggle('hidden', isArchived);

            if (deleteBtn) {
                if (data.chat_room.can_delete && !isArchived) {
                    deleteBtn.classList.remove('hidden');
                } else {
                    deleteBtn.classList.add('hidden');
                }
            }
        }
    } catch (error) {
        console.error('Error loading chat room:', error);
    }
}

// Load messages for current room
async function loadMessages(roomId) {
    try {
        const response = await fetch(`/chat/rooms/${roomId}/messages`);
        if (response.status === 403) {
            handleChatAccessLost(roomId);
            return;
        }

        const data = await response.json();
        
        if (data.messages) {
            displayMessages(data.messages);
            clearChatRoomUnreadCount(roomId);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Display messages in the chat interface
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const currentUserId = {{ Auth::id() }};
    const isArchivedChat = Boolean(currentChatRoomDetails?.is_archived || messages.some(message => message.is_archived));
    currentMessagesById = Object.fromEntries(messages.map(message => [message.id, message]));
    renderPinnedMessages(messages);
    const wasNearBottom = isMessagesNearBottom();
    
    container.innerHTML = messages.map(message => {
        const isOwnMessage = message.user.id === currentUserId;
        const messageClass = isOwnMessage ? 'ml-auto bg-blue-600 text-white' : 'mr-auto bg-gray-100 text-gray-900';
        const avatarHtml = renderMessageAvatar(message.user);
        const imageFallbackUrl = message.public_file_url && message.public_file_url !== message.file_url
            ? escapeHtml(message.public_file_url)
            : '';
        
        return `
            <div class="flex ${isOwnMessage ? 'justify-end' : 'justify-start'} gap-2 group" data-message-id="${message.id}">
                ${!isOwnMessage ? avatarHtml : ''}
                <div class="flex max-w-[calc(100%-2.5rem)] flex-col ${isOwnMessage ? 'items-end' : 'items-start'}">
                <div class="w-fit max-w-full sm:max-w-md lg:max-w-lg px-4 py-2 rounded-lg ${messageClass} relative message-content">
                    ${!isOwnMessage ? `<div class="text-xs font-medium mb-1 ${isOwnMessage ? 'text-blue-100' : 'text-gray-600'}">${escapeHtml(message.user.name)}</div>` : ''}
                    ${message.is_pinned ? `<span class="mb-2 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-[11px] font-semibold text-yellow-800">Pinned</span>` : ''}
                    ${message.reply_to ? `
                        <button type="button" onclick="scrollToMessage(${message.reply_to.id})"
                                class="mb-2 block w-full rounded border-l-4 ${isOwnMessage ? 'border-blue-200 bg-blue-500 text-blue-50' : 'border-blue-500 bg-white text-gray-700'} px-2 py-1 text-left text-xs">
                            <span class="block font-semibold">${escapeHtml(message.reply_to.user_name)}</span>
                            <span class="block truncate">${escapeHtml(message.reply_to.message)}</span>
                        </button>
                    ` : ''}
                    ${message.message && !(message.file_url && message.message === 'File shared') ? `<div class="break-words text-sm whitespace-pre-wrap">${linkifyMessageText(message.message, isOwnMessage)}</div>` : ''}
                    ${message.file_url ? `
                        <div class="mt-2">
                            ${message.is_image ? 
                                `<a href="${message.file_url}" target="_blank" rel="noopener" class="block">
                                    <img src="${message.file_url}" data-fallback-src="${imageFallbackUrl}" alt="${escapeHtml(message.file_name || 'Shared image')}" class="max-h-80 w-auto max-w-full rounded-md object-contain border ${isOwnMessage ? 'border-blue-400' : 'border-gray-200'}" loading="lazy" onerror="handleChatImageError(this)">
                                </a>` :
                                `<a href="${message.file_url}" target="_blank" rel="noopener" class="inline-flex items-center break-all text-xs underline decoration-1 underline-offset-2">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    ${escapeHtml(message.file_name || 'Attachment')} (${escapeHtml(message.file_size || '')})
                                </a>`
                            }
                        </div>
                    ` : ''}
                    <div class="hidden">
                        <div class="text-xs ${isOwnMessage ? 'text-blue-100' : 'text-gray-500'}">
                            ${message.created_at_human}
                            ${message.seen_by_count > 0 ? `<span class="ml-2">✓ Seen by ${message.seen_by_count}</span>` : ''}
                        </div>
                        <div class="hidden">
                            ${message.can_delete ? `
                                <button onclick="showDeleteOptions(${message.id})" class="opacity-0 group-hover:opacity-100 transition-opacity text-xs ${isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-gray-400 hover:text-gray-600'}">
                                    ⋯
                                </button>
                            ` : ''}
                            <button onclick="showEmojiPicker(${message.id})" class="opacity-0 group-hover:opacity-100 transition-opacity text-xs ${isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-gray-400 hover:text-gray-600'}" title="Add Reaction">
                                😊
                            </button>
                        </div>
                    </div>
                    <div class="mt-1 flex items-center justify-between gap-3 text-xs ${isOwnMessage ? 'text-blue-100' : 'text-gray-500'}">
                        <div class="min-w-0 truncate">
                            ${message.created_at_human}
                            ${message.is_edited ? `<span class="ml-2">Edited</span>` : ''}
                            ${message.seen_by_count > 0 ? `<span class="ml-2">Seen by ${message.seen_by_count}</span>` : ''}
                        </div>
                        <div class="flex shrink-0 items-center gap-0.5 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                            ${!isArchivedChat ? `
                            <button type="button" onclick="startReply(${message.id})" class="inline-flex h-6 w-6 items-center justify-center rounded-full ${isOwnMessage ? 'hover:bg-blue-500 hover:text-white' : 'hover:bg-gray-200 hover:text-gray-800'}" title="Reply">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 014 4v2"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="togglePin(${message.id})" class="inline-flex h-6 w-6 items-center justify-center rounded-full ${message.is_pinned ? 'bg-yellow-100 text-yellow-800' : (isOwnMessage ? 'hover:bg-blue-500 hover:text-white' : 'hover:bg-gray-200 hover:text-yellow-700')}" title="${message.is_pinned ? 'Unpin message' : 'Pin message'}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4l5 5-4 4v5l-2 2-5-5-4 4-1-1 4-4-5-5 2-2h5l4-4z"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="showEmojiPicker(${message.id})" class="inline-flex h-6 w-6 items-center justify-center rounded-full ${isOwnMessage ? 'hover:bg-blue-500 hover:text-white' : 'hover:bg-gray-200 hover:text-gray-800'}" title="React">
                                <span class="text-xs leading-none">+</span>
                            </button>
                            ` : ''}
                            ${message.can_edit ? `
                                <button type="button" onclick="startEdit(${message.id})" class="inline-flex h-6 w-6 items-center justify-center rounded-full ${isOwnMessage ? 'hover:bg-blue-500 hover:text-white' : 'hover:bg-gray-200 hover:text-gray-800'}" title="Edit">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            ` : ''}
                            ${message.can_delete ? `
                                <button type="button" onclick="showDeleteOptions(${message.id})" class="inline-flex h-6 w-6 items-center justify-center rounded-full ${isOwnMessage ? 'hover:bg-blue-500 hover:text-white' : 'hover:bg-red-50 hover:text-red-700'}" title="Delete">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    ${message.reactions && message.reactions.length > 0 ? `
                        <div class="flex flex-wrap gap-1 mt-2">
                            ${message.reactions.map(reaction => `
                                <button ${isArchivedChat ? 'type="button" disabled' : `onclick="toggleReaction(${message.id}, '${reaction.emoji}')"`}
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs border transition-colors ${reaction.user_ids.includes(currentUserId) ? 'bg-blue-100 border-blue-300 text-blue-800' : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'}"
                                        title="${escapeHtml(reaction.users.map(u => u.name).join(', '))}">
                                    ${reaction.emoji} ${reaction.count}
                                </button>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                </div>
                ${isOwnMessage ? avatarHtml : ''}
            </div>
        `;
    }).join('');
    
    // Mark messages as seen
    const unseenMessageIds = messages.filter(msg => !msg.is_seen && msg.user.id !== currentUserId).map(msg => msg.id);
    if (unseenMessageIds.length > 0) {
        markMessagesAsSeen(unseenMessageIds);
    }
    
    // Smart scroll: auto-scroll on initial load, after sending message, or if user was near the bottom.
    if (isInitialLoad || shouldScrollToBottom || wasNearBottom) {
        scrollMessagesToBottom();
        isInitialLoad = false; // Reset flag after initial scroll
        shouldScrollToBottom = false; // Reset flag after forced scroll
    }
    updateScrollToBottomButton();
}

function isMessagesNearBottom() {
    const container = document.getElementById('messagesContainer');
    if (!container || container.classList.contains('hidden')) return true;

    return container.scrollHeight - container.scrollTop - container.clientHeight < 120;
}

function scrollMessagesToBottom(smooth = false) {
    const container = document.getElementById('messagesContainer');
    const button = document.getElementById('scrollToBottomButton');
    if (!container) return;

    button?.classList.add('hidden');
    const scrollToLatest = () => {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto'
        });
    };

    scrollToLatest();
    requestAnimationFrame(scrollToLatest);

    if (smooth) {
        setTimeout(updateScrollToBottomButton, 350);
        return;
    }

    updateScrollToBottomButton();
}

function updateScrollToBottomButton() {
    const button = document.getElementById('scrollToBottomButton');
    const container = document.getElementById('messagesContainer');
    if (!button || !container || container.classList.contains('hidden')) return;

    button.classList.toggle('hidden', isMessagesNearBottom());
}

function renderPinnedMessages(messages) {
    const panel = document.getElementById('pinnedMessagesPanel');
    const count = document.getElementById('pinnedMessagesCount');
    currentPinnedMessages = messages.filter(message => message.is_pinned);

    if (!currentPinnedMessages.length) {
        panel.classList.add('hidden');
        panel.innerHTML = '';
        count.classList.add('hidden');
        count.textContent = '';
        return;
    }

    count.classList.remove('hidden');
    count.textContent = currentPinnedMessages.length > 9 ? '9+' : currentPinnedMessages.length;

    panel.innerHTML = `
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-yellow-900">Pinned messages</div>
                <div class="mt-2 space-y-2">
                    ${currentPinnedMessages.map(message => `
                        <button type="button" onclick="scrollToMessage(${message.id}); closePinnedMessagesPanel();" class="block w-full rounded-md border border-yellow-200 bg-white px-3 py-2 text-left text-sm text-gray-700 hover:border-yellow-300 hover:bg-yellow-100">
                            <span class="block font-semibold text-gray-900">${escapeHtml(message.user.name)}</span>
                            <span class="block truncate">${escapeHtml(message.message || message.file_name || 'Attachment')}</span>
                        </button>
                    `).join('')}
                </div>
            </div>
            <button type="button" onclick="closePinnedMessagesPanel()" class="shrink-0 rounded-md p-1 text-yellow-700 hover:bg-yellow-100" title="Close pinned messages">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

    if (!panel.classList.contains('hidden')) {
        positionPinnedMessagesPanel();
    }
}

function togglePinnedMessagesPanel() {
    if (!currentPinnedMessages.length) {
        showNotification('Pinned Messages', 'There are no pinned messages in this chat yet.');
        return;
    }

    positionPinnedMessagesPanel();
    document.getElementById('pinnedMessagesPanel').classList.toggle('hidden');
}

function closePinnedMessagesPanel() {
    document.getElementById('pinnedMessagesPanel').classList.add('hidden');
}

function positionPinnedMessagesPanel() {
    const panel = document.getElementById('pinnedMessagesPanel');
    const header = document.getElementById('chatHeader');
    if (!panel || !header) return;

    panel.style.top = window.innerWidth < 1024
        ? `${header.getBoundingClientRect().bottom + 8}px`
        : `${header.offsetHeight + 8}px`;
}

function startReply(messageId) {
    if (currentChatRoomDetails?.is_archived) {
        showNotification('Archived chats', 'This conversation is saved as read-only history.');
        return;
    }

    const message = currentMessagesById[messageId];
    if (!message) return;

    selectedReplyMessage = message;
    document.getElementById('replyPreviewUser').textContent = message.user.name;
    document.getElementById('replyPreviewText').textContent = message.message || message.file_name || 'Attachment';
    document.getElementById('replyPreview').classList.remove('hidden');
    document.getElementById('messageText').focus();
}

function clearReply() {
    selectedReplyMessage = null;
    document.getElementById('replyPreview').classList.add('hidden');
    document.getElementById('replyPreviewUser').textContent = '';
    document.getElementById('replyPreviewText').textContent = '';
}

function startEdit(messageId) {
    if (currentChatRoomDetails?.is_archived) {
        showNotification('Archived chats', 'This conversation is saved as read-only history.');
        return;
    }

    const message = currentMessagesById[messageId];
    if (!message || !message.can_edit) return;

    selectedEditMessage = message;
    clearReply();
    clearFileSelection();
    hideMentionSuggestions();

    const textarea = document.getElementById('messageText');
    textarea.value = message.message || '';
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);

    document.getElementById('editPreviewText').textContent = message.message || '';
    document.getElementById('editPreview').classList.remove('hidden');
    document.getElementById('fileInput').disabled = true;
}

function clearEdit() {
    selectedEditMessage = null;
    document.getElementById('editPreview').classList.add('hidden');
    document.getElementById('editPreviewText').textContent = '';
    document.getElementById('fileInput').disabled = false;
    document.getElementById('messageText').value = '';
}

function scrollToMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) return;

    messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    messageElement.classList.add('ring-2', 'ring-yellow-300', 'rounded-lg');
    setTimeout(() => {
        messageElement.classList.remove('ring-2', 'ring-yellow-300', 'rounded-lg');
    }, 1400);
}

async function togglePin(messageId) {
    if (!currentRoomId) return;

    if (currentChatRoomDetails?.is_archived) {
        showNotification('Archived chats', 'This conversation is saved as read-only history.');
        return;
    }

    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${messageId}/pin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json')
            ? await response.json()
            : { error: await response.text() };

        if (result.success) {
            loadMessages(currentRoomId);
        } else {
            showNotification('Error', result.error || 'Failed to update pinned message', 'error');
        }
    } catch (error) {
        console.error('Error toggling pin:', error);
        showNotification('Network Error', 'Failed to update pinned message. Please check your connection.', 'error');
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeRegExp(value) {
    return String(value ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function linkifyMessageText(value, isOwnMessage = false) {
    const escapedText = escapeHtml(value);
    const linkClass = isOwnMessage
        ? 'underline decoration-1 underline-offset-2 text-blue-50 hover:text-white'
        : 'underline decoration-1 underline-offset-2 text-blue-700 hover:text-blue-900';
    const mentionClass = 'font-semibold text-blue-300';

    const linkedText = escapedText.replace(/((?:https?:\/\/|www\.)[^\s<]+)/gi, (match) => {
        const trailingPunctuation = match.match(/[.,!?;:)]+$/)?.[0] || '';
        const cleanUrl = trailingPunctuation ? match.slice(0, -trailingPunctuation.length) : match;
        const href = cleanUrl.startsWith('www.') ? `https://${cleanUrl}` : cleanUrl;

        return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="${linkClass}">${cleanUrl}</a>${trailingPunctuation}`;
    });

    const mentionPatterns = [
        'everyone',
        ...mentionableParticipants().map(participant => participant.name),
    ]
        .filter(Boolean)
        .sort((a, b) => b.length - a.length)
        .map(name => escapeRegExp(escapeHtml(name)));

    if (!mentionPatterns.length) {
        return linkedText;
    }

    const mentionRegex = new RegExp(`(^|\\s)@(${mentionPatterns.join('|')})(?=$|[\\s.,!?;:)])`, 'gi');

    return linkedText
        .split(/(<a\b[^>]*>.*?<\/a>)/gi)
        .map(segment => {
            if (/^<a\b/i.test(segment)) {
                return segment;
            }

            return segment.replace(mentionRegex, (match, prefix, name) => {
                return `${prefix}<span class="${mentionClass}">@${name}</span>`;
            });
        })
        .join('');
}

function currentMentionSearch(textarea) {
    const cursor = textarea.selectionStart ?? textarea.value.length;
    const beforeCursor = textarea.value.slice(0, cursor);
    const match = beforeCursor.match(/(^|\s)@([^\s@]*)$/);

    if (!match) return null;

    return {
        start: beforeCursor.length - match[2].length - 1,
        end: cursor,
        query: match[2].toLowerCase(),
    };
}

function mentionableParticipants() {
    const currentUserId = {{ Auth::id() }};

    return (currentChatRoomDetails?.mention_participants || currentChatRoomDetails?.participants || [])
        .filter(participant => participant.id !== currentUserId);
}

async function ensureMentionParticipantsLoaded() {
    if (!currentRoomId || mentionParticipantsLoadedForRoomId === currentRoomId || mentionParticipantsLoading) {
        return;
    }

    mentionParticipantsLoading = true;

    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}`);
        const data = await response.json();

        if (data.chat_room) {
            currentChatRoomDetails = data.chat_room;
            mentionParticipantsLoadedForRoomId = currentRoomId;
        }
    } catch (error) {
        console.error('Error refreshing mention participants:', error);
    } finally {
        mentionParticipantsLoading = false;
    }
}

async function updateMentionSuggestions() {
    const textarea = document.getElementById('messageText');
    const suggestions = document.getElementById('mentionSuggestions');
    if (!textarea || !suggestions) return;

    const search = currentMentionSearch(textarea);
    if (!search) {
        hideMentionSuggestions();
        return;
    }

    if (mentionParticipantsLoadedForRoomId !== currentRoomId) {
        const existingParticipants = mentionableParticipants();

        if (!existingParticipants.length) {
            suggestions.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Loading people...</div>';
            suggestions.classList.remove('hidden');
        }

        await ensureMentionParticipantsLoaded();
    }

    const latestSearch = currentMentionSearch(textarea);
    if (!latestSearch) {
        hideMentionSuggestions();
        return;
    }

    const participants = mentionableParticipants();
    const matches = participants
        .filter(participant => participant.name.toLowerCase().includes(latestSearch.query))
        .slice(0, 8);
    const showEveryone = participants.length > 0 && 'everyone'.includes(latestSearch.query);

    if (!matches.length && !showEveryone) {
        hideMentionSuggestions();
        return;
    }

    const everyoneHtml = showEveryone ? `
        <button type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-semibold text-gray-800 hover:bg-blue-50 hover:text-blue-700"
                data-mention-everyone="true">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-amber-100 bg-amber-50 text-xs font-semibold text-amber-700">
                @
            </div>
            <span class="min-w-0 flex-1 truncate">Everyone</span>
        </button>
    ` : '';

    const participantHtml = matches.map(participant => `
        <button type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
                data-mention-id="${participant.id}"
                data-mention-name="${escapeHtml(participant.name)}">
            ${renderParticipantFallbackAvatar(participant.name, participant.initials)}
            <span class="min-w-0 flex-1 truncate">${escapeHtml(participant.name)}</span>
        </button>
    `).join('');

    suggestions.innerHTML = everyoneHtml + participantHtml;

    suggestions.querySelectorAll('button[data-mention-everyone]').forEach(button => {
        button.addEventListener('mousedown', event => {
            event.preventDefault();
            insertEveryoneMention();
        });
    });

    suggestions.querySelectorAll('button[data-mention-id]').forEach(button => {
        button.addEventListener('mousedown', event => {
            event.preventDefault();
            insertMention(Number(button.dataset.mentionId), button.dataset.mentionName || '');
        });
    });

    suggestions.classList.remove('hidden');
}

function insertMention(userId, name) {
    const textarea = document.getElementById('messageText');
    if (!textarea) return;

    const search = currentMentionSearch(textarea);
    if (!search) return;

    const before = textarea.value.slice(0, search.start);
    const after = textarea.value.slice(search.end);
    const mentionText = `@${name} `;

    textarea.value = `${before}${mentionText}${after}`;
    selectedMentionIds.add(userId);
    hideMentionSuggestions();
    textarea.focus();
    const cursor = before.length + mentionText.length;
    textarea.setSelectionRange(cursor, cursor);
}

function insertEveryoneMention() {
    const textarea = document.getElementById('messageText');
    if (!textarea) return;

    const search = currentMentionSearch(textarea);
    if (!search) return;

    const before = textarea.value.slice(0, search.start);
    const after = textarea.value.slice(search.end);
    const mentionText = '@everyone ';

    textarea.value = `${before}${mentionText}${after}`;
    hideMentionSuggestions();
    textarea.focus();
    const cursor = before.length + mentionText.length;
    textarea.setSelectionRange(cursor, cursor);
}

function hideMentionSuggestions() {
    const suggestions = document.getElementById('mentionSuggestions');
    if (suggestions) {
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    }
}

function selectedMentionIdsForMessage(messageText) {
    const normalizedMessage = messageText.toLowerCase();

    return mentionableParticipants()
        .filter(participant => selectedMentionIds.has(participant.id))
        .filter(participant => normalizedMessage.includes(`@${participant.name.toLowerCase()}`))
        .map(participant => participant.id);
}

function renderMessageAvatar(user) {
    const initials = escapeHtml(user?.initials || '?');
    const name = escapeHtml(user?.name || 'User');

    if (user?.avatar_url) {
        return `
            <img src="${escapeHtml(user.avatar_url)}" alt="${name}" class="mt-1 h-8 w-8 shrink-0 rounded-full border border-gray-200 object-cover">
        `;
    }

    return `
        <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-xs font-semibold text-blue-700">
            ${initials}
        </div>
    `;
}

function handleChatImageError(image) {
    const fallbackSrc = image.dataset.fallbackSrc;

    if (fallbackSrc && image.src !== fallbackSrc) {
        image.src = fallbackSrc;
        image.dataset.fallbackSrc = '';
        return;
    }

    image.replaceWith(Object.assign(document.createElement('div'), {
        className: 'rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700',
        textContent: 'Image could not be loaded.'
    }));
}

// Delete message functions
function showDeleteOptions(messageId) {
    // Store the message ID in the modal
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    buttonsContainer.setAttribute('data-message-id', messageId);
    
    document.getElementById('deleteMessageModal').classList.remove('hidden');
}

function closeDeleteMessageModal() {
    document.getElementById('deleteMessageModal').classList.add('hidden');
    
    // Clear the stored message ID
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    buttonsContainer.removeAttribute('data-message-id');
}

async function performDeleteMessage(deleteFor) {
    // Get the message ID from the modal
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    const messageId = buttonsContainer.getAttribute('data-message-id');
    
    if (!messageId) {
        showNotification('Error', 'No message selected for deletion', 'error');
        return;
    }
    
    closeDeleteMessageModal();
    await deleteMessage(messageId, deleteFor);
}

// Send message
document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!currentRoomId) return;

    if (currentChatRoomDetails?.is_archived) {
        showNotification('Archived chats', 'This conversation is saved as read-only history.');
        return;
    }
    
    const messageText = document.getElementById('messageText').value.trim();
    const fileInput = document.getElementById('fileInput');

    if (selectedEditMessage) {
        await submitEditMessage(messageText);
        return;
    }
    
    if (!messageText && !fileInput.files[0]) return;
    
    const formData = new FormData();
    if (messageText) formData.append('message', messageText);
    if (fileInput.files[0]) formData.append('file', fileInput.files[0]);
    if (selectedReplyMessage) formData.append('reply_to_id', selectedReplyMessage.id);
    selectedMentionIdsForMessage(messageText).forEach(userId => {
        formData.append('mention_user_ids[]', userId);
    });
    
    try {
        const sentRoomId = currentRoomId;
        const response = await fetch(`/chat/rooms/${sentRoomId}/messages`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : { error: await response.text() };
        
        if (data.success) {
            document.getElementById('messageText').value = '';
            selectedMentionIds.clear();
            hideMentionSuggestions();
            clearFileSelection();
            clearReply();
            shouldScrollToBottom = true; // Force scroll after sending message
            loadMessages(currentRoomId);
        } else {
            if (response.status === 403) {
                handleChatAccessLost(sentRoomId, data.error || 'You are no longer in this chat room.');
                return;
            }

            let errorMessage = data.error || 'Failed to send message';

            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join('<br>');
            }

            showNotification('Error', errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showNotification('Network Error', 'Failed to send message. Please check your connection.', 'error');
    }
});

async function submitEditMessage(messageText) {
    if (!selectedEditMessage || !messageText) return;

    try {
        const editedMessageId = selectedEditMessage.id;
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${editedMessageId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message: messageText })
        });

        const result = await response.json();

        if (result.success) {
            clearEdit();
            loadMessages(currentRoomId);
            showNotification('Success', 'Message updated successfully', 'success');
            return;
        }

        let errorMessage = result.error || 'Failed to update message';

        if (result.errors) {
            errorMessage = Object.values(result.errors).flat().join('<br>');
        }

        showNotification('Error', errorMessage, 'error');
    } catch (error) {
        console.error('Error editing message:', error);
        showNotification('Network Error', 'Failed to update message. Please check your connection.', 'error');
    }
}

// Handle Enter key in message input
function handleMessageKeydown(event) {
    if (event.key === 'Escape') {
        if (selectedEditMessage) {
            clearEdit();
        }
        hideMentionSuggestions();
        return;
    }

    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        hideMentionSuggestions();
        document.getElementById('sendMessageForm').dispatchEvent(new Event('submit'));
    }
}

// File upload functions
function toggleFileUpload() {
    document.getElementById('fileInput').click();
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = `${file.name} (${formatFileSize(file.size)})`;
        document.getElementById('filePreview').classList.remove('hidden');
    }
}

function clearFileSelection() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').classList.add('hidden');
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${Math.round(size * 100) / 100} ${units[unitIndex]}`;
}

// Modal functions
function openCreateRoomModal() {
    document.getElementById('createRoomModal').classList.remove('hidden');
}

function closeCreateRoomModal() {
    document.getElementById('createRoomModal').classList.add('hidden');
    document.getElementById('createRoomForm').reset();
}

// Create room form submission
document.getElementById('createRoomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.participants = [{{ Auth::id() }}]; // Add current user as participant
    delete data._token;
    
    // Validate required fields
    if (!data.name || !data.type) {
        alert('Please fill in all required fields');
        return;
    }

    const submitButton = document.getElementById('createRoomSubmit');
    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';
    
    try {
        const response = await fetch('{{ route('chat.rooms.store') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json')
            ? await response.json()
            : { error: await response.text() };
        
        if (result.success) {
            closeCreateRoomModal();
            showNotification('Success', 'Chat room created successfully!', 'success');
            setTimeout(() => location.reload(), 700); // Refresh to show new room
        } else {
            // Show validation errors
            if (result.errors) {
                let errorMessage = 'Validation errors:<br>';
                Object.keys(result.errors).forEach(field => {
                    errorMessage += `<strong>${field}:</strong> ${result.errors[field].join(', ')}<br>`;
                });
                showNotification('Validation Error', errorMessage, 'error');
            } else {
                showNotification('Error', result.error || 'Failed to create chat room', 'error');
            }
        }
    } catch (error) {
        console.error('Error creating room:', error);
        HTMLFormElement.prototype.submit.call(this);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Create Room';
    }
});

// Enhanced chat features

// Participant management functions
function showAddParticipantsModal() {
    if (!currentRoomId) return;
    
    document.getElementById('addParticipantsModal').classList.remove('hidden');
    loadAvailableUsers();
}

function closeAddParticipantsModal() {
    document.getElementById('addParticipantsModal').classList.add('hidden');
    document.getElementById('addParticipantsForm').reset();
}

function showParticipants() {
    if (!currentRoomId) return;
    
    document.getElementById('participantsModal').classList.remove('hidden');
    loadParticipants();
}

function closeParticipantsModal() {
    document.getElementById('participantsModal').classList.add('hidden');
}

// Load available users for adding to chat room
async function loadAvailableUsers() {
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/available-users`);
        const data = await response.json();
        
        const container = document.getElementById('availableUsersList');
        
        if (data.success && data.users && data.users.length > 0) {
            container.innerHTML = data.users.map(user => `
                <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                    <input type="checkbox" id="user_${user.id}" value="${user.id}" class="rounded border-gray-300">
                    <label for="user_${user.id}" class="flex-1 cursor-pointer">
                        <div class="font-medium text-gray-900">${escapeHtml(user.name)}</div>
                        <div class="text-sm text-gray-500">${escapeHtml(user.email)} (${escapeHtml(user.role)})</div>
                    </label>
                </div>
            `).join('');
        } else {
            let message = 'No available users to add';
            if (data.error) {
                message = `Error: ${data.error}`;
                console.error('API Error:', data.error);
            } else if (data.users && data.users.length === 0) {
                message = 'All verified users are already participants';
            }
            container.innerHTML = `<p class="text-gray-500 text-center py-4">${message}</p>`;
        }
    } catch (error) {
        console.error('Error loading available users:', error);
        document.getElementById('availableUsersList').innerHTML = '<p class="text-red-500 text-center py-4">Network error loading users</p>';
    }
}

// Load current participants
async function loadParticipants() {
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}`);
        const data = await response.json();
        
        const container = document.getElementById('participantsList');
        
        if (data.chat_room && data.chat_room.participants) {
            currentChatRoomDetails = data.chat_room;
            const currentUserId = {{ Auth::id() }};
            const canManageParticipants = Boolean(data.chat_room.can_manage_participants);
            const addButton = document.getElementById('participantsAddButton');
            addButton?.classList.toggle('hidden', !canManageParticipants);

            container.innerHTML = data.chat_room.participants.map(participant => `
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <div class="flex min-w-0 items-center space-x-3">
                        ${renderParticipantAvatar(participant)}
                        <div class="min-w-0">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="truncate font-medium text-gray-900">${escapeHtml(participant.name)}</span>
                                ${participant.is_admin ? '<span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Admin</span>' : ''}
                                ${participant.is_chat_participant === false ? '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Group member</span>' : ''}
                            </div>
                            <div class="text-sm text-gray-500">${participant.is_admin ? 'Can manage this chat' : escapeHtml(participant.pivot.role)}</div>
                        </div>
                    </div>
                    <div class="ml-3 flex shrink-0 items-center gap-2">
                        ${canManageParticipants && participant.is_chat_participant !== false && !participant.is_admin ? `
                            <button onclick="updateParticipantRole(${participant.id}, 'admin')" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                Make admin
                            </button>
                            <button onclick="removeParticipant(${participant.id})" class="text-xs font-medium text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        ` : ''}
                        ${canManageParticipants && participant.is_chat_participant !== false && participant.is_admin ? `
                            <button onclick="updateParticipantRole(${participant.id}, 'member')" class="text-xs font-medium text-gray-600 hover:text-gray-800">
                                Remove admin
                            </button>
                        ` : ''}
                        ${canManageParticipants && participant.is_chat_participant !== false && participant.is_admin && participant.id !== currentUserId ? `
                            <button onclick="removeParticipant(${participant.id})" class="text-xs font-medium text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        ` : ''}
                        ${participant.id === currentUserId && participant.is_admin ? `
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">You are admin</span>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading participants:', error);
    }
}

function renderParticipantAvatar(participant) {
    const name = escapeHtml(participant.name || 'Participant');

    if (participant.avatar_url) {
        return `<img src="${escapeHtml(participant.avatar_url)}" alt="${name}" class="h-8 w-8 shrink-0 rounded-full border border-gray-200 object-cover" onerror="handleParticipantAvatarError(this)">`;
    }

    return renderParticipantFallbackAvatar(name, participant.initials);
}

function renderParticipantFallbackAvatar(name = 'Participant', initials = '?') {
    const safeInitials = escapeHtml(initials || '?');

    return `
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-xs font-semibold text-blue-700" title="${name}">
            ${safeInitials}
        </div>
    `;
}

function handleParticipantAvatarError(image) {
    const name = escapeHtml(image.alt || 'Participant');
    image.insertAdjacentHTML('afterend', renderParticipantFallbackAvatar(name, '?'));
    image.remove();
}

async function updateParticipantRole(userId, role) {
    const isMakingAdmin = role === 'admin';

    showConfirmation(
        isMakingAdmin ? 'Make Admin' : 'Remove Admin',
        isMakingAdmin
            ? 'Make this participant a chat admin? Admins can add members and manage participant roles.'
            : 'Remove admin access from this participant? They will stay in the chat as a member.',
        () => performUpdateParticipantRole(userId, role),
        isMakingAdmin ? 'Make Admin' : 'Remove Admin',
        isMakingAdmin ? 'confirm' : 'danger'
    );
}

async function performUpdateParticipantRole(userId, role) {
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/participant-role`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ user_id: userId, role })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Success', result.message, 'success');
            loadChatRoom(currentRoomId);
            loadParticipants();
        } else {
            showNotification('Error', result.error || 'Failed to update participant role', 'error');
        }
    } catch (error) {
        console.error('Error updating participant role:', error);
        showNotification('Network Error', 'Failed to update participant role. Please check your connection.', 'error');
    }
}

async function toggleChatRoomPin(roomId, event) {
    event?.stopPropagation();

    try {
        const response = await fetch(`/chat/rooms/${roomId}/pin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Success', result.message, 'success');
            setTimeout(() => location.reload(), 300);
        } else {
            showNotification('Error', result.error || 'Failed to update chat room pin', 'error');
        }
    } catch (error) {
        console.error('Error toggling chat room pin:', error);
        showNotification('Network Error', 'Failed to update chat room pin. Please check your connection.', 'error');
    }
}

// Add participants form submission
document.getElementById('addParticipantsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const selectedUsers = Array.from(document.querySelectorAll('#availableUsersList input[type="checkbox"]:checked'))
                              .map(checkbox => parseInt(checkbox.value));
    
    if (selectedUsers.length === 0) {
        showNotification('Selection Required', 'Please select at least one user to add', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/participants`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ user_ids: selectedUsers })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAddParticipantsModal();
            showNotification('Success', result.message, 'success');
            loadParticipants();
        } else {
            showNotification('Error', result.error || 'Failed to add participants', 'error');
        }
    } catch (error) {
        console.error('Error adding participants:', error);
        showNotification('Network Error', 'Failed to add participants. Please check your connection.', 'error');
    }
});

// Remove participant
async function removeParticipant(userId) {
    showConfirmation(
        'Remove Participant',
        'Are you sure you want to remove this participant from the chat room?',
        () => performRemoveParticipant(userId),
        'Remove',
        'danger'
    );
}

async function performRemoveParticipant(userId) {
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/participants`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            loadParticipants(); // Refresh the list
        } else {
            showNotification('Error', result.error || 'Failed to remove participant', 'error');
        }
    } catch (error) {
        console.error('Error removing participant:', error);
        showNotification('Network Error', 'Failed to remove participant. Please check your connection.', 'error');
    }
}

// Delete message function (called from modal)
async function deleteMessage(messageId, deleteFor = 'self') {
    if (!messageId) {
        showNotification('Error', 'Invalid message ID', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ delete_for: deleteFor })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadMessages(currentRoomId); // Refresh messages
            showNotification('Success', 'Message deleted successfully', 'success');
        } else {
            showNotification('Error', result.error || 'Failed to delete message', 'error');
        }
    } catch (error) {
        console.error('Error deleting message:', error);
        showNotification('Network Error', 'Failed to delete message. Please check your connection.', 'error');
    }
}

// Mark messages as seen
async function markMessagesAsSeen(messageIds) {
    if (!messageIds.length) return;
    
    try {
        await fetch(`/chat/rooms/${currentRoomId}/messages/seen`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message_ids: messageIds })
        });
    } catch (error) {
        console.error('Error marking messages as seen:', error);
    }
}

// Emoji reaction functions
let currentMessageIdForReaction = null;

function showEmojiPicker(messageId) {
    currentMessageIdForReaction = messageId;
    document.getElementById('emojiPickerModal').classList.remove('hidden');
}

function closeEmojiPicker() {
    document.getElementById('emojiPickerModal').classList.add('hidden');
    currentMessageIdForReaction = null;
}

async function addReaction(emoji) {
    if (!currentMessageIdForReaction) return;
    
    await toggleReaction(currentMessageIdForReaction, emoji);
    closeEmojiPicker();
}

async function toggleReaction(messageId, emoji) {
    if (!currentRoomId) return;
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${messageId}/reactions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ emoji: emoji })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update the message reactions in real-time
            updateMessageReactions(messageId, result.reactions);
        } else {
            showNotification('Error', result.error || 'Failed to toggle reaction', 'error');
        }
    } catch (error) {
        console.error('Error toggling reaction:', error);
        showNotification('Network Error', 'Failed to toggle reaction. Please check your connection.', 'error');
    }
}

function updateMessageReactions(messageId, reactions) {
    // Find the message element and update its reactions
    const messageElements = document.querySelectorAll('[data-message-id]');
    
    messageElements.forEach(element => {
        if (element.dataset.messageId == messageId) {
            const reactionsContainer = element.querySelector('.reactions-container');
            if (reactionsContainer) {
                reactionsContainer.remove();
            }
            
            if (reactions && reactions.length > 0) {
                const currentUserId = {{ Auth::id() }};
                const reactionsHtml = `
                    <div class="reactions-container flex flex-wrap gap-1 mt-2">
                        ${reactions.map(reaction => `
                            <button onclick="toggleReaction(${messageId}, '${reaction.emoji}')" 
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs border transition-colors ${reaction.user_ids.includes(currentUserId) ? 'bg-blue-100 border-blue-300 text-blue-800' : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'}"
                                    title="${escapeHtml(reaction.users.map(u => u.name).join(', '))}">
                                ${reaction.emoji} ${reaction.count}
                            </button>
                        `).join('')}
                    </div>
                `;
                
                const messageContent = element.querySelector('.message-content');
                if (messageContent) {
                    messageContent.insertAdjacentHTML('beforeend', reactionsHtml);
                }
            }
        }
    });
}

// New features functions

// Leave chat room
async function leaveChatRoom() {
    if (!currentRoomId) return;
    
    showConfirmation(
        'Leave Chat Room',
        'Are you sure you want to leave this chat room? Admins must remove their admin access first and make sure another member is admin.',
        () => performLeaveChatRoom(),
        'Leave Room',
        'danger'
    );
}

async function performLeaveChatRoom() {
    const leftRoomId = currentRoomId;
    
    try {
        const response = await fetch(`/chat/rooms/${leftRoomId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            removeChatRoomFromList(leftRoomId);
            clearActiveChatRoom(leftRoomId);
        } else {
            showNotification('Error', result.error || 'Failed to leave chat room', 'error');
        }
    } catch (error) {
        console.error('Error leaving chat room:', error);
        showNotification('Network Error', 'Failed to leave chat room. Please check your connection.', 'error');
    }
}

// Delete chat room
async function deleteChatRoom() {
    if (!currentRoomId) return;
    
    showConfirmation(
        'Delete Chat Room',
        'Are you sure you want to delete this chat room? <br><br><strong>This action cannot be undone and all messages will be permanently deleted.</strong>',
        () => performDeleteChatRoom(),
        'Delete Room',
        'danger'
    );
}

async function performDeleteChatRoom() {
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            setTimeout(() => location.reload(), 1500); // Refresh to update room list
        } else {
            showNotification('Error', result.error || 'Failed to delete chat room', 'error');
        }
    } catch (error) {
        console.error('Error deleting chat room:', error);
        showNotification('Network Error', 'Failed to delete chat room. Please check your connection.', 'error');
    }
}

// Typing indicator functions
let typingTimeout;
let isTyping = false;

// Send typing status
async function sendTypingStatus(typing) {
    if (!currentRoomId) return;
    
    try {
        await fetch(`/chat/rooms/${currentRoomId}/typing`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ is_typing: typing })
        });
    } catch (error) {
        console.error('Error sending typing status:', error);
    }
}

// Get typing users
async function getTypingUsers() {
    if (!currentRoomId) return;
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/typing`);
        const result = await response.json();
        
        if (result.success) {
            displayTypingIndicator(result.typing_users);
        }
    } catch (error) {
        console.error('Error getting typing users:', error);
    }
}

// Display typing indicator
function displayTypingIndicator(typingUsers) {
    const indicator = document.getElementById('typingIndicator');
    
    if (typingUsers.length === 0) {
        indicator.classList.add('hidden');
        return;
    }
    
    let message = '';
    if (typingUsers.length === 1) {
        message = `${typingUsers[0].user_name} is typing...`;
    } else if (typingUsers.length === 2) {
        message = `${typingUsers[0].user_name} and ${typingUsers[1].user_name} are typing...`;
    } else {
        message = `${typingUsers[0].user_name} and ${typingUsers.length - 1} others are typing...`;
    }
    
    indicator.textContent = message;
    indicator.classList.remove('hidden');
}

// Handle typing in message input
function handleTyping() {
    if (!isTyping) {
        isTyping = true;
        sendTypingStatus(true);
    }
    
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        isTyping = false;
        sendTypingStatus(false);
    }, 3000); // Stop typing indicator after 3 seconds of inactivity
}

// Delete button visibility is now handled in loadChatRoom function

// Typing indicator polling is now handled in selectChatRoom function

// Add typing event listener to message input
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageText');
    if (messageInput) {
        messageInput.addEventListener('input', handleTyping);
        messageInput.addEventListener('input', updateMentionSuggestions);
        messageInput.addEventListener('blur', () => setTimeout(hideMentionSuggestions, 150));
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                // Stop typing when sending message
                if (isTyping) {
                    isTyping = false;
                    sendTypingStatus(false);
                }
            }
        });
    }
});
</script>
</div>
</x-app-layout>
