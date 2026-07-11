<?php

namespace App\Http\Controllers;

use App\Jobs\SendChatMentionDigestEmail;
use App\Models\AdviserStudent;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\AppNotification;
use App\Models\MessageReaction;
use App\Models\Project;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\GoogleChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends BaseController
{
    protected ?GoogleChatService $googleChatService = null;


    /**
     * Get or create GoogleChatService instance
     */
    private function getGoogleChatService(): GoogleChatService
    {
        if (!$this->googleChatService) {
            $this->googleChatService = new GoogleChatService();
        }
        return $this->googleChatService;
    }

    /**
     * Display chat interface
     */
    public function index()
    {
        return $this->chatPage(false);
    }

    /**
     * Display archived chat history.
     */
    public function archived()
    {
        return $this->chatPage(true);
    }

    private function chatPage(bool $archivedOnly)
    {
        $user = Auth::user();
        $this->abortIfWebsiteAdmin();

        $accessibleProjects = $user->accessibleProjects()
            ->with(['owner', 'adviser', 'members'])
            ->get();

        $accessibleProjects->each(fn (Project $project) => $this->ensureProjectChatRoom($project));
        ChatRoom::whereIn('project_id', $accessibleProjects->pluck('id'))
            ->with(['project.owner', 'project.adviser', 'project.members'])
            ->get()
            ->each(fn (ChatRoom $chatRoom) => $this->syncProjectChatParticipants($chatRoom));
        
        $accessibleProjectIds = $accessibleProjects->pluck('id');
        $leaveTrackingEnabled = Schema::hasTable('chat_participant_leaves');
        $roomPinningEnabled = Schema::hasTable('chat_room_pins');
        $pinnedRoomIds = $roomPinningEnabled
            ? DB::table('chat_room_pins')->where('user_id', $user->id)->pluck('chat_room_id')
            : collect();

        // Include participant rooms, rooms the user created, and project rooms unless the user left them.
        $chatRooms = ChatRoom::query()
            ->where('is_active', true)
            ->where(function ($rooms) use ($user, $accessibleProjectIds, $leaveTrackingEnabled) {
                $rooms->where('created_by', $user->id)
                    ->orWhereHas('participants', function ($participants) use ($user) {
                        $participants->where('users.id', $user->id);
                    })
                    ->when($accessibleProjectIds->isNotEmpty(), function ($rooms) use ($accessibleProjectIds, $user, $leaveTrackingEnabled) {
                        $rooms->orWhere(function ($projectRooms) use ($accessibleProjectIds, $user, $leaveTrackingEnabled) {
                            $projectRooms->whereIn('project_id', $accessibleProjectIds);

                            if ($leaveTrackingEnabled) {
                                $projectRooms->whereNotExists(function ($leaves) use ($user) {
                                    $leaves->select(DB::raw(1))
                                        ->from('chat_participant_leaves')
                                        ->whereColumn('chat_participant_leaves.chat_room_id', 'chat_rooms.id')
                                        ->where('chat_participant_leaves.user_id', $user->id);
                                });
                            }
                        });
                    });
            })
            ->with(['project', 'latestMessage.user'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('id')
            ->values();

        $archivedRoomIds = ChatRoom::archivedIdsForRoomIds($chatRooms->pluck('id'));
        $unreadCountsByRoom = $this->unreadCountsForRooms($chatRooms->pluck('id'), $user);

        $chatRooms = $chatRooms
            ->map(function ($room) use ($user, $pinnedRoomIds, $archivedRoomIds, $unreadCountsByRoom) {
                if (!$room->hasParticipant($user) && !$this->hasLeftChatRoom($room, $user) && $this->canAccessChatRoom($room, $user)) {
                    $room->addParticipant($user, $room->created_by === $user->id ? 'admin' : 'member');
                }

                $room->unread_count = (int) ($unreadCountsByRoom[$room->id] ?? 0);
                $room->is_pinned = $pinnedRoomIds->contains($room->id);
                $room->is_archived = $archivedRoomIds->contains($room->id);
                return $room;
            })
            ->filter(function ($room) use ($archivedOnly, $user) {
                if ($room->is_archived && ! $this->canViewArchivedChatRoom($room, $user)) {
                    return false;
                }

                if ($archivedOnly) {
                    return $room->is_archived;
                }

                return $user->isStudentGroupRole() || ! $room->is_archived;
            })
            ->values()
            ->sort(function ($firstRoom, $secondRoom) {
                if ($firstRoom->is_pinned !== $secondRoom->is_pinned) {
                    return $firstRoom->is_pinned ? -1 : 1;
                }

                return $secondRoom->updated_at <=> $firstRoom->updated_at;
            })
            ->values();

        $availableProjects = $archivedOnly
            ? collect()
            : $accessibleProjects
                ->filter(fn (Project $project) => $this->canCreateProjectChatRoom($project, $user))
                ->values();
        $isArchivedChatsPage = $archivedOnly;

        return view('chat.index', compact('chatRooms', 'availableProjects', 'isArchivedChatsPage'));
    }

    private function unreadCountsForRooms($roomIds, User $user)
    {
        $roomIds = collect($roomIds)->filter()->unique()->values();

        if ($roomIds->isEmpty()) {
            return collect();
        }

        return ChatMessage::query()
            ->select('chat_messages.chat_room_id', DB::raw('COUNT(*) as unread_count'))
            ->join('chat_participants', function ($join) use ($user) {
                $join->on('chat_participants.chat_room_id', '=', 'chat_messages.chat_room_id')
                    ->where('chat_participants.user_id', '=', $user->id);
            })
            ->whereIn('chat_messages.chat_room_id', $roomIds)
            ->where('chat_messages.message_type', '!=', 'system')
            ->where('chat_messages.user_id', '!=', $user->id)
            ->where(function ($query) {
                $query->whereNull('chat_participants.last_read_at')
                    ->orWhereColumn('chat_messages.created_at', '>', 'chat_participants.last_read_at');
            })
            ->groupBy('chat_messages.chat_room_id')
            ->pluck('unread_count', 'chat_messages.chat_room_id');
    }

    /**
     * Create a new chat room
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $this->abortIfWebsiteAdmin();

        if (!$user->canLeadGroup() && !$user->isTeacher()) {
            return $this->chatRoomCreateError($request, 'Only group leaders and advisers can create chat rooms', 403);
        }

        if (!$request->filled('project_id')) {
            $defaultProject = $this->defaultChatProjectFor($user);

            if ($defaultProject) {
                $request->merge(['project_id' => $defaultProject->id]);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:project,direct,group',
            'project_id' => 'nullable|exists:projects,id',
            'participants' => 'nullable|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            if (!$request->expectsJson()) {
                return redirect()
                    ->route('chat.index')
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Get participants
            $participantIds = $request->input('participants', [$user->id]);
            $project = null;

            if ($request->project_id) {
                $project = $project ?: Project::with(['owner', 'adviser', 'members'])->findOrFail($request->project_id);

                if (!$this->canCreateProjectChatRoom($project, $user)) {
                    return $this->chatRoomCreateError($request, 'You cannot create a chat room for this project', 403);
                }

                $participants = $this->getProjectChatParticipants($project);
                $participantIds = $participants->pluck('id')->all();
            } else {
                $participants = User::whereIn('id', $participantIds)->get();
            }

            // Validate participants exist
            if ($participants->count() !== count($participantIds)) {
                return $this->chatRoomCreateError($request, 'Some participants not found', 422);
            }

            // Add current user to participants if not already included
            $currentUserId = Auth::id();
            if (!in_array($currentUserId, $participantIds)) {
                $currentUser = Auth::user();
                $participants->push($currentUser);
            }

            // Create chat room using Google Chat service
            $chatRoom = $this->getGoogleChatService()->createChatRoom(
                $request->name,
                $request->description ?? '',
                $participants->all(),
                $request->project_id
            );

            if (!$chatRoom) {
                \Log::error('ChatRoom creation returned null', [
                    'name' => $request->name,
                    'participants' => $participantIds,
                    'project_id' => $request->project_id
                ]);
                return $this->chatRoomCreateError($request, 'Failed to create chat room. Please check the logs for details.', 500);
            }

            if ($chatRoom->project_id) {
                $this->syncProjectChatParticipants($chatRoom->load(['project.owner', 'project.adviser', 'project.members']));
            }

            if (!$request->expectsJson()) {
                return redirect()
                    ->route('chat.index')
                    ->with('success', 'Chat room created successfully!');
            }

            return response()->json([
                'success' => true,
                'chat_room' => $chatRoom->load(['participants', 'project']),
                'message' => 'Chat room created successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Exception in chat room creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->chatRoomCreateError($request, 'Failed to create chat room: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get messages for a specific chat room
     */
    public function getMessages(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $this->syncChatRoomProjectParticipants($chatRoom);

            if (!$chatRoom->hasParticipant(Auth::user()) && !$this->hasLeftChatRoom($chatRoom, Auth::user()) && $this->canAccessChatRoom($chatRoom, Auth::user())) {
                $chatRoom->addParticipant(Auth::user(), $chatRoom->created_by === Auth::id() ? 'admin' : 'member');
            }

            // Check if user is a participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $isArchived = $this->isArchivedChatRoom($chatRoom);
            if ($isArchived && ! $this->canViewArchivedChatRoom($chatRoom, Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $currentUserId = Auth::id();
            
            $messages = $chatRoom->messages()
                                ->with(['user:id,firstname,lastname,profile_picture_path,updated_at', 'replyTo.user:id,firstname,lastname', 'reactions.user:id,firstname,lastname'])
                                ->orderBy('created_at', 'asc')
                                ->get()
                                ->filter(function ($message) use ($currentUserId) {
                                    // Filter out messages deleted for this user
                                    $deletedForUsers = $message->deleted_for_users ?? [];
                                    return !in_array($currentUserId, $deletedForUsers);
                                })
                                ->map(function ($message) use ($currentUserId, $isArchived) {
                                    // Handle case where user might be deleted
                                    $user = $message->user;
                                    $userName = $user ? ($user->firstname . ' ' . $user->lastname) : 'Deleted User';
                                    $userId = $user ? $user->id : null;
                                    $userInitials = $user
                                        ? strtoupper(substr($user->firstname ?? '', 0, 1) . substr($user->lastname ?? '', 0, 1))
                                        : '?';
                                    
                                    // Check if current user has seen this message
                                    $seenBy = $message->seen_by ?? [];
                                    $isSeenByCurrentUser = in_array($currentUserId, $seenBy);
                                    
                                    // Get seen by info (excluding current user)
                                    $seenByOthers = array_filter($seenBy, function($id) use ($currentUserId) {
                                        return $id !== $currentUserId;
                                    });
                                    
                                    return [
                                        'id' => $message->id,
                                        'message' => $message->message,
                                        'message_type' => $message->message_type,
                                        'is_pinned' => $message->is_pinned,
                                        'reply_to' => $message->replyTo ? [
                                            'id' => $message->replyTo->id,
                                            'message' => $message->replyTo->message,
                                            'user_name' => $message->replyTo->user
                                                ? $message->replyTo->user->firstname . ' ' . $message->replyTo->user->lastname
                                                : 'Deleted User',
                                        ] : null,
                                        'user' => [
                                            'id' => $userId,
                                            'name' => $userName,
                                            'initials' => $userInitials,
                                            'avatar_url' => $user && $user->profile_picture_path
                                                ? route('profile.picture', $user) . '?v=' . optional($user->updated_at)->timestamp
                                                : null,
                                        ],
                                        'file_url' => $message->getFileUrl(),
                                        'public_file_url' => $message->getPublicFileUrl(),
                                        'file_name' => $message->file_name,
                                        'file_size' => $message->getFormattedFileSize(),
                                        'is_image' => $message->isImage(),
                                        'is_edited' => $message->is_edited,
                                        'is_seen' => $isSeenByCurrentUser,
                                        'seen_by_count' => count($seenByOthers),
                                        'is_archived' => $isArchived,
                                        'can_delete' => ! $isArchived && $userId === $currentUserId,
                                        'can_edit' => ! $isArchived && $userId === $currentUserId && $message->message_type === 'text',
                                        'reactions' => $message->getReactionsSummary(),
                                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                                        'created_at_human' => $message->created_at->diffForHumans(),
                                    ];
                                })
                                ->values(); // Reset array keys after filtering

            // Mark messages as read
            $chatRoom->participants()
                     ->wherePivot('user_id', Auth::id())
                     ->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

            return response()->json(['messages' => $messages]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading messages', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Failed to load messages: ' . $e->getMessage()], 500);
        }
    }

    public function showFile(ChatMessage $message)
    {
        $this->abortIfWebsiteAdmin();

        $user = Auth::user();
        $chatRoom = $message->chatRoom;

        if ($chatRoom) {
            $this->syncChatRoomProjectParticipants($chatRoom);
        }

        if (!$user || !$chatRoom || (!$chatRoom->hasParticipant($user) && ($this->hasLeftChatRoom($chatRoom, $user) || !$this->canAccessChatRoom($chatRoom, $user)))) {
            abort(403);
        }

        if (!$message->file_path || !Storage::disk('public')->exists($message->file_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($message->file_path), [
            'Content-Type' => $message->file_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($message->file_name ?: 'attachment') . '"',
        ]);
    }

    /**
     * Send a message to a chat room
     */
    public function sendMessage(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        $user = Auth::user();

        $this->syncChatRoomProjectParticipants($chatRoom);

        if (!$chatRoom->hasParticipant($user) && !$this->hasLeftChatRoom($chatRoom, $user) && $this->canAccessChatRoom($chatRoom, $user)) {
            $chatRoom->addParticipant($user, $chatRoom->created_by === $user->id ? 'admin' : 'member');
        }

        // Check if user is a participant
        if (!$chatRoom->hasParticipant($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($this->isArchivedChatRoom($chatRoom)) {
            return $this->archivedChatResponse();
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string|max:2000',
            'file' => 'nullable|file|max:10240', // 10MB max
            'reply_to_id' => 'nullable|exists:chat_messages,id',
            'mention_user_ids' => 'nullable|array',
            'mention_user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $fileData = null;

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('chat_files', $filename, 'public');

                $fileData = [
                    'path' => $filePath,
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file',
                ];
            }

            $replyToId = $request->input('reply_to_id');
            if ($replyToId) {
                $replyToMessage = ChatMessage::where('id', $replyToId)
                    ->where('chat_room_id', $chatRoom->id)
                    ->first();

                if (!$replyToMessage) {
                    return response()->json(['error' => 'Reply message was not found in this chat room'], 422);
                }
            }

            // Send message through Google Chat service
            $message = $this->getGoogleChatService()->sendChatMessage(
                $chatRoom,
                $request->message ?? 'File shared',
                $fileData
            );

            if (!$message) {
                return response()->json(['error' => 'Failed to send message'], 500);
            }

            if ($replyToId) {
                $message->update(['reply_to_id' => $replyToId]);
                $message->load(['replyTo.user']);
            }

            $this->createMentionNotifications($chatRoom, $message, $user, $request->input('mention_user_ids', []));

            $chatRoom->touch();

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'is_pinned' => $message->is_pinned,
                    'reply_to' => $message->replyTo ? [
                        'id' => $message->replyTo->id,
                        'message' => $message->replyTo->message,
                        'user_name' => $message->replyTo->user
                            ? $message->replyTo->user->firstname . ' ' . $message->replyTo->user->lastname
                            : 'Deleted User',
                    ] : null,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->firstname . ' ' . $message->user->lastname,
                        'initials' => strtoupper(substr($message->user->firstname ?? '', 0, 1) . substr($message->user->lastname ?? '', 0, 1)),
                        'avatar_url' => $message->user->profile_picture_path
                            ? route('profile.picture', $message->user) . '?v=' . optional($message->user->updated_at)->timestamp
                            : null,
                    ],
                    'file_url' => $message->getFileUrl(),
                    'public_file_url' => $message->getPublicFileUrl(),
                    'file_name' => $message->file_name,
                    'file_size' => $message->getFormattedFileSize(),
                    'is_image' => $message->isImage(),
                    'is_edited' => $message->is_edited,
                    'can_edit' => $message->message_type === 'text',
                    'can_delete' => true,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $message->created_at->diffForHumans(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send chat message', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get chat room details
     */
    public function show(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        $this->syncChatRoomProjectParticipants($chatRoom);

        if (!$chatRoom->hasParticipant(Auth::user()) && !$this->hasLeftChatRoom($chatRoom, Auth::user()) && $this->canAccessChatRoom($chatRoom, Auth::user())) {
            $chatRoom->addParticipant(Auth::user(), $chatRoom->created_by === Auth::id() ? 'admin' : 'member');
        }

        // Check if user is a participant
        if (!$chatRoom->hasParticipant(Auth::user())) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $chatRoom->load(['participants:id,firstname,lastname,profile_picture_path,updated_at', 'project:id,title,owner_id,adviser_id']);
        $currentUser = Auth::user();
        $archivedRelationship = $this->archivedRelationshipForChatRoom($chatRoom);
        $isArchived = (bool) $archivedRelationship;
        if ($isArchived && ! $this->canViewArchivedChatRoom($chatRoom, $currentUser)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $canManageParticipants = ! $isArchived && $this->isChatAdmin($chatRoom, $currentUser);
        $isRoomPinned = Schema::hasTable('chat_room_pins')
            && DB::table('chat_room_pins')
                ->where('chat_room_id', $chatRoom->id)
                ->where('user_id', $currentUser->id)
                ->exists();
        $displayParticipants = $this->displayableUsersForRoom($chatRoom);
        $mentionParticipants = $this->mentionableUsersForRoom($chatRoom);

        return response()->json([
            'chat_room' => [
                'id' => $chatRoom->id,
                'name' => $chatRoom->name,
                'description' => $chatRoom->description,
                'type' => $chatRoom->type,
                'created_by' => $chatRoom->created_by,
                'is_owner' => $canManageParticipants,
                'is_admin' => $canManageParticipants,
                'can_manage_participants' => $canManageParticipants,
                'can_delete' => $canManageParticipants,
                'is_pinned' => $isRoomPinned,
                'is_archived' => $isArchived,
                'archived_at' => optional($archivedRelationship?->archived_at)->format('Y-m-d H:i:s'),
                'archived_label' => $isArchived ? 'Archived chats' : null,
                'can_send_messages' => ! $isArchived,
                'project' => $chatRoom->project ? [
                    'id' => $chatRoom->project->id,
                    'title' => $chatRoom->project->title,
                    'owner_id' => $chatRoom->project->owner_id,
                    'adviser_id' => $chatRoom->project->adviser_id,
                ] : null,
                'participants' => $displayParticipants->map(function ($user) use ($chatRoom) {
                    $initials = strtoupper(substr($user->firstname ?? '', 0, 1) . substr($user->lastname ?? '', 0, 1));
                    $isParticipantAdmin = ($user->pivot->role ?? 'member') === 'admin';

                    return [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'initials' => $initials ?: '?',
                        'avatar_url' => $user->profile_picture_path
                            ? route('profile.picture', $user) . '?v=' . optional($user->updated_at)->timestamp
                            : null,
                        'is_owner' => $isParticipantAdmin,
                        'is_admin' => $isParticipantAdmin,
                        'is_chat_participant' => ! ($user->is_group_only_participant ?? false),
                        'pivot' => [
                            'role' => $isParticipantAdmin ? 'admin' : ($user->pivot->role ?? 'member')
                        ]
                    ];
                }),
                'mention_participants' => $mentionParticipants->map(function ($user) {
                    $initials = strtoupper(substr($user->firstname ?? '', 0, 1) . substr($user->lastname ?? '', 0, 1));

                    return [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'initials' => $initials ?: '?',
                        'avatar_url' => $user->profile_picture_path
                            ? route('profile.picture', $user) . '?v=' . optional($user->updated_at)->timestamp
                            : null,
                    ];
                })->values(),
                'unread_count' => $chatRoom->getUnreadCountForUser(Auth::user()),
            ]
        ]);
    }

    /**
     * Create a project-based chat room
     */
    public function createProjectChat(Project $project): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user can manage the project
            if (!$project->canEdit(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $chatRoom = $this->ensureProjectChatRoom($project->load(['owner', 'adviser', 'members']));

            return response()->json([
                'success' => true,
                'chat_room' => $chatRoom->load(['participants', 'project']),
                'message' => 'Project chat room created successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create project chat: ' . $e->getMessage()], 500);
        }
    }

    private function ensureProjectChatRoom(Project $project): ChatRoom
    {
        $chatRoom = ChatRoom::firstOrCreate(
            [
                'project_id' => $project->id,
                'type' => 'project',
            ],
            [
                'name' => $project->title . ' - Project Chat',
                'description' => 'Chat room for project: ' . $project->title,
                'created_by' => $project->owner_id ?: Auth::id(),
                'google_space_id' => null,
                'is_active' => true,
            ]
        );

        $this->syncProjectChatParticipants($chatRoom->setRelation('project', $project));

        return $chatRoom;
    }

    private function syncChatRoomProjectParticipants(ChatRoom $chatRoom): void
    {
        if (!$chatRoom->project_id) {
            return;
        }

        $chatRoom->loadMissing(['project.owner', 'project.adviser', 'project.members']);
        $this->syncProjectChatParticipants($chatRoom);
    }

    private function syncProjectChatParticipants(ChatRoom $chatRoom): void
    {
        if ($this->isArchivedChatRoom($chatRoom)) {
            return;
        }

        $project = $chatRoom->project ?: $chatRoom->project()->with(['owner', 'adviser', 'members'])->first();

        if (!$project) {
            return;
        }

        $participants = $this->getProjectChatParticipants($project);

        $allowedIds = $participants->pluck('id')->all();

        foreach ($participants as $participant) {
            if ($this->hasLeftChatRoom($chatRoom, $participant)) {
                continue;
            }

            if ($chatRoom->hasParticipant($participant)) {
                continue;
            }

            $role = $participant->id === $project->owner_id || $participant->id === $chatRoom->created_by
                ? 'admin'
                : ($participant->isTeacher() ? 'moderator' : 'member');

            $chatRoom->addParticipant($participant, $role);
        }

        if ($chatRoom->creator) {
            if (!$this->hasLeftChatRoom($chatRoom, $chatRoom->creator) && !$chatRoom->hasParticipant($chatRoom->creator)) {
                $chatRoom->addParticipant($chatRoom->creator, 'admin');
            }
        }

        $retainedIds = collect($allowedIds)
            ->push($chatRoom->created_by)
            ->filter()
            ->unique()
            ->all();

        $chatRoom->participants()
            ->whereNotIn('users.id', $retainedIds)
            ->detach();
    }

    private function getProjectChatParticipants(Project $project)
    {
        $students = collect([$project->owner])
            ->merge($project->members)
            ->filter()
            ->unique('id');

        $approvedAdvisers = $students
            ->flatMap(fn (User $student) => $student->advisers()->approved()->active()->with('adviser')->get()->pluck('adviser'))
            ->filter();

        return collect([$project->owner])
            ->merge($project->adviser ? [$project->adviser] : [])
            ->merge($approvedAdvisers)
            ->merge($project->members)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function hasLeftChatRoom(ChatRoom $chatRoom, User $user): bool
    {
        if (!Schema::hasTable('chat_participant_leaves')) {
            return false;
        }

        return DB::table('chat_participant_leaves')
            ->where('chat_room_id', $chatRoom->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function clearLeftChatRoom(ChatRoom $chatRoom, User $user): void
    {
        if (!Schema::hasTable('chat_participant_leaves')) {
            return;
        }

        DB::table('chat_participant_leaves')
            ->where('chat_room_id', $chatRoom->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    private function recordLeftChatRoom(ChatRoom $chatRoom, User $user): void
    {
        if (!Schema::hasTable('chat_participant_leaves')) {
            return;
        }

        $lastReadAt = $chatRoom->participants()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot
            ?->last_read_at;

        DB::table('chat_participant_leaves')->updateOrInsert(
            [
                'chat_room_id' => $chatRoom->id,
                'user_id' => $user->id,
            ],
            [
                'left_at' => $lastReadAt ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function createMentionNotifications(ChatRoom $chatRoom, ChatMessage $message, User $sender, array $mentionUserIds = []): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        $mentionedUsers = $this->mentionedUsers($chatRoom, (string) $message->message, $sender, $mentionUserIds);

        foreach ($mentionedUsers as $mentionedUser) {
            AppNotification::updateOrCreate(
                [
                    'user_id' => $mentionedUser->id,
                    'type' => 'chat_mention',
                    'source_type' => 'chat_message',
                    'source_id' => $message->id,
                ],
                [
                    'title' => 'You were mentioned in chat',
                    'body' => $sender->name . ' mentioned you in ' . $chatRoom->name . '.',
                    'action_url' => route('chat.index', ['room' => $chatRoom->id]),
                    'data' => [
                        'chat_room_id' => $chatRoom->id,
                        'chat_message_id' => $message->id,
                        'sender_id' => $sender->id,
                    ],
                    'read_at' => null,
                ]
            );

            $this->sendMentionEmailNotification($mentionedUser, $sender, $chatRoom);
        }
    }

    private function sendMentionEmailNotification(User $recipient, User $sender, ChatRoom $chatRoom): void
    {
        if (! $recipient->email) {
            return;
        }

        $cacheKey = "chat_mention_email_window:{$recipient->id}";
        $window = Cache::get($cacheKey);

        if (! $window) {
            Cache::put($cacheKey, [
                'count' => 1,
                'digest_scheduled' => false,
            ], now()->addMinutes(20));

            app(EmailNotificationService::class)->sendChatMentionReceived($recipient, $sender, $chatRoom);

            return;
        }

        $window['count'] = ((int) ($window['count'] ?? 0)) + 1;
        $shouldScheduleDigest = empty($window['digest_scheduled']);
        $window['digest_scheduled'] = true;

        Cache::put($cacheKey, $window, now()->addMinutes(20));

        if (! $shouldScheduleDigest) {
            return;
        }

        if (! Schema::hasTable('jobs')) {
            Log::warning('Skipped chat mention digest scheduling because the jobs table is missing.', [
                'recipient_id' => $recipient->id,
            ]);

            return;
        }

        SendChatMentionDigestEmail::dispatch($recipient->id)
            ->delay(now()->addMinutes(15))
            ->onQueue('emails');
    }

    private function mentionedUsers(ChatRoom $chatRoom, string $message, User $sender, array $mentionUserIds = [])
    {
        $normalizedMessage = str($message)->lower()->squish()->toString();
        $explicitMentionIds = collect($mentionUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();
        $mentionsEveryone = preg_match('/(^|[\s(])@everyone(?=$|[\s.,!?;:)])/u', $normalizedMessage) === 1;

        return $this->mentionableUsersForRoom($chatRoom)
            ->where('id', '!=', $sender->id)
            ->filter(function (User $participant) use ($normalizedMessage, $explicitMentionIds, $mentionsEveryone) {
                if ($mentionsEveryone) {
                    return true;
                }

                if ($explicitMentionIds->contains($participant->id)) {
                    return true;
                }

                $tokens = collect([
                    $participant->firstname,
                    $participant->lastname,
                    $participant->name,
                    str_replace(' ', '', $participant->name),
                    str_replace(' ', '.', $participant->name),
                    str_replace(' ', '_', $participant->name),
                    str($participant->email)->before('@')->toString(),
                ])
                    ->filter()
                    ->map(fn ($token) => '@' . str((string) $token)->lower()->squish()->toString())
                    ->unique();

                return $tokens->contains(function ($token) use ($normalizedMessage) {
                    $pattern = '/(^|[\s(])' . preg_quote($token, '/') . '(?=$|[\s.,!?;:)])/u';

                    return preg_match($pattern, $normalizedMessage) === 1;
                });
            })
            ->values();
    }

    private function mentionableUsersForRoom(ChatRoom $chatRoom)
    {
        return $this->displayableUsersForRoom($chatRoom)
            ->values();
    }

    private function displayableUsersForRoom(ChatRoom $chatRoom)
    {
        $chatRoom->loadMissing(['participants', 'project.owner', 'project.adviser', 'project.members']);

        if (! $chatRoom->project_id || ! $chatRoom->project) {
            return $chatRoom->participants->values();
        }

        $chatParticipantsById = $chatRoom->participants->keyBy('id');

        return $this->getProjectChatParticipants($chatRoom->project)
            ->reject(fn (User $user) => $this->hasLeftChatRoom($chatRoom, $user))
            ->map(function (User $user) use ($chatParticipantsById) {
                $chatParticipant = $chatParticipantsById->get($user->id);

                if ($chatParticipant) {
                    return $chatParticipant;
                }

                $user->setRelation('pivot', (object) [
                    'role' => 'member',
                ]);
                $user->is_group_only_participant = true;

                return $user;
            })
            ->unique('id')
            ->values();
    }

    private function isChatAdmin(ChatRoom $chatRoom, User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        $participant = $chatRoom->participants()->where('users.id', $user->id)->first();

        return $participant && $participant->pivot->role === 'admin';
    }

    private function chatAdminCount(ChatRoom $chatRoom): int
    {
        return $chatRoom->participants()
            ->wherePivot('role', 'admin')
            ->count();
    }

    private function canCreateProjectChatRoom(Project $project, User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        if ($project->canEdit($user)) {
            return ! $project->isArchivedForUser($user);
        }

        return $user->isTeacher()
            && $project->canAccess($user)
            && ! $project->isArchivedForUser($user);
    }

    private function defaultChatProjectFor(User $user): ?Project
    {
        if ($user->canLeadGroup()) {
            return $user->ownedProjects()
                ->with(['owner', 'adviser', 'members'])
                ->latest()
                ->first();
        }

        if ($user->isTeacher()) {
            return $user->accessibleProjects()
                ->with(['owner', 'adviser', 'members'])
                ->latest()
                ->first();
        }

        return null;
    }

    private function chatRoomCreateError(Request $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return redirect()
            ->route('chat.index')
            ->withInput()
            ->with('error', $message);
    }

    private function canAccessChatRoom(ChatRoom $chatRoom, User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        if ($chatRoom->created_by === $user->id) {
            return true;
        }

        if ($chatRoom->project_id) {
            $project = $chatRoom->project ?: $chatRoom->project()->with(['owner', 'adviser', 'members'])->first();

            return $project ? $project->canAccess($user) : false;
        }

        return false;
    }

    private function archivedRelationshipForChatRoom(ChatRoom $chatRoom): ?AdviserStudent
    {
        return $chatRoom->archivedRelationship();
    }

    private function isArchivedChatRoom(ChatRoom $chatRoom): bool
    {
        return $chatRoom->isArchived();
    }

    private function canViewArchivedChatRoom(ChatRoom $chatRoom, User $user): bool
    {
        $archivedRelationship = $this->archivedRelationshipForChatRoom($chatRoom);

        if (! $archivedRelationship) {
            return true;
        }

        if ((int) $archivedRelationship->student_id === (int) $user->id) {
            return true;
        }

        if ((int) $archivedRelationship->adviser_id === (int) $user->id) {
            return true;
        }

        $project = $chatRoom->project ?: $chatRoom->project()->first();

        return $project
            && (
                (int) $project->owner_id === (int) $user->id
                || $project->members()->where('users.id', $user->id)->exists()
            );
    }

    private function archivedChatResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'This chat has been archived. Chat history is available, but new chat activity is disabled.',
            'is_archived' => true,
        ], 423);
    }

    private function canBeAddedToChatRoom(ChatRoom $chatRoom, User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        $project = $chatRoom->project_id
            ? ($chatRoom->project ?: $chatRoom->project()->with(['owner', 'adviser', 'members'])->first())
            : null;

        if ($user->canLeadGroup()) {
            return $project && (int) $project->owner_id === (int) $user->id;
        }

        if ($project) {
            return $project->canAccess($user);
        }

        return !$user->canLeadGroup();
    }

    private function abortIfWebsiteAdmin(): void
    {
        if (Auth::user()?->isAdmin()) {
            abort(403, 'Website administrators cannot access chat rooms.');
        }
    }

    /**
     * Add participants to a chat room
     */
    public function addParticipants(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$this->isChatAdmin($chatRoom, $currentUser)) {
                return response()->json(['error' => 'Only chat admins can add participants'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $addedUsers = [];
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);

                if (!$user || !$this->canBeAddedToChatRoom($chatRoom, $user)) {
                    return response()->json([
                        'error' => 'Only this group leader can be added to this chat room.'
                    ], 422);
                }

                if ($user) {
                    $this->clearLeftChatRoom($chatRoom, $user);
                }

                if ($user && !$chatRoom->hasParticipant($user)) {
                    $chatRoom->addParticipant($user, 'member');
                    $addedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($addedUsers) . ' participants added successfully',
                'added_users' => $addedUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error adding participants', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to add participants: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove participant from chat room
     */
    public function removeParticipant(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$this->isChatAdmin($chatRoom, $currentUser)) {
                return response()->json(['error' => 'Only chat admins can remove participants'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $userToRemove = User::find($request->user_id);
            
            if ($chatRoom->hasParticipant($userToRemove)) {
                if ($this->isChatAdmin($chatRoom, $userToRemove) && $this->chatAdminCount($chatRoom) <= 1) {
                    return response()->json([
                        'error' => 'Assign another chat admin before removing this admin.'
                    ], 422);
                }

                $chatRoom->participants()->detach($userToRemove->id);
                $this->recordLeftChatRoom($chatRoom, $userToRemove);
                ChatMessage::create([
                    'chat_room_id' => $chatRoom->id,
                    'user_id' => $currentUser->id,
                    'message' => $userToRemove->firstname . ' ' . $userToRemove->lastname . ' was removed from the chat',
                    'message_type' => 'system'
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Participant removed successfully'
                ]);
            } else {
                return response()->json(['error' => 'User is not a participant'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Error removing participant', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to remove participant: ' . $e->getMessage()], 500);
        }
    }

    public function updateParticipantRole(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$this->isChatAdmin($chatRoom, $currentUser)) {
                return response()->json(['error' => 'Only chat admins can update participant roles'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'role' => 'required|in:member,admin,moderator',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $participantUser = User::findOrFail($request->user_id);

            if (!$chatRoom->hasParticipant($participantUser)) {
                return response()->json(['error' => 'Roles can only be updated for current participants'], 422);
            }

            $newRole = $request->input('role');
            $currentRole = $participantUser->pivot->role ?? 'member';

            if ($currentRole === 'admin' && $newRole !== 'admin' && $this->chatAdminCount($chatRoom) <= 1) {
                return response()->json([
                    'error' => 'Assign another chat admin before removing admin access.'
                ], 422);
            }

            $chatRoom->participants()->updateExistingPivot($participantUser->id, ['role' => $newRole]);

            ChatMessage::create([
                'chat_room_id' => $chatRoom->id,
                'user_id' => $currentUser->id,
                'message' => $participantUser->firstname . ' ' . $participantUser->lastname . ' is now a chat ' . $newRole,
                'message_type' => 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participant role updated successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating chat participant role', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to update participant role: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$chatRoom->hasParticipant($currentUser)) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            if ($message->user_id !== $currentUser->id) {
                return response()->json(['error' => 'You can only edit your own messages'], 403);
            }

            if ($message->message_type !== 'text') {
                return response()->json(['error' => 'Only text messages can be edited'], 422);
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $updatedMessage = trim($request->input('message'));

            if ($updatedMessage === '') {
                return response()->json(['errors' => ['message' => ['The message field is required.']]], 422);
            }

            $message->update([
                'message' => $updatedMessage,
                'is_edited' => true,
                'edited_at' => now(),
            ]);

            $chatRoom->touch();

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'is_edited' => $message->is_edited,
                    'edited_at' => optional($message->edited_at)->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error editing message', [
                'message_id' => $message->id,
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to edit message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Request $request, ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            // Check if user owns the message or is admin
            $currentUser = Auth::user();
            $userParticipant = $chatRoom->participants()->where('user_id', $currentUser->id)->first();
            $isAdmin = $userParticipant && in_array($userParticipant->pivot->role, ['admin', 'creator']);
            
            if ($message->user_id !== $currentUser->id && !$isAdmin) {
                return response()->json(['error' => 'You can only delete your own messages'], 403);
            }

            $validator = Validator::make($request->all(), [
                'delete_for' => 'required|in:self,everyone',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->delete_for === 'everyone') {
                // Only message owner or admin can delete for everyone
                if ($message->user_id !== $currentUser->id && !$isAdmin) {
                    return response()->json(['error' => 'Only message owner or admin can delete for everyone'], 403);
                }
                
                // Delete the message completely
                $message->delete();
                $messageText = 'Message deleted for everyone';
            } else {
                // Delete for self only - mark as deleted for this user
                $message->update([
                    'deleted_for_users' => array_unique(array_merge(
                        $message->deleted_for_users ?? [],
                        [$currentUser->id]
                    ))
                ]);
                $messageText = 'Message deleted for you';
            }

            return response()->json([
                'success' => true,
                'message' => $messageText,
                'delete_type' => $request->delete_for
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting message', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark messages as seen
     */
    public function markAsSeen(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'message_ids' => 'required|array|min:1',
                'message_ids.*' => 'exists:chat_messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            
            // Update seen status for messages
            foreach ($request->message_ids as $messageId) {
                $message = ChatMessage::find($messageId);
                if ($message && $message->chat_room_id === $chatRoom->id) {
                    $seenBy = $message->seen_by ?? [];
                    if (!in_array($currentUser->id, $seenBy)) {
                        $seenBy[] = $currentUser->id;
                        $message->update(['seen_by' => $seenBy]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as seen'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error marking messages as seen', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to mark messages as seen: ' . $e->getMessage()], 500);
        }
    }

    public function togglePin(ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$chatRoom->hasParticipant($currentUser)) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            $message->update(['is_pinned' => !$message->is_pinned]);

            return response()->json([
                'success' => true,
                'is_pinned' => $message->is_pinned,
                'message' => $message->is_pinned ? 'Message pinned' : 'Message unpinned',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling pin', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to update pinned message: ' . $e->getMessage()], 500);
        }
    }

    public function toggleRoomPin(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();

            if (!$chatRoom->hasParticipant($currentUser) && !$this->canAccessChatRoom($chatRoom, $currentUser)) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            if (!Schema::hasTable('chat_room_pins')) {
                return response()->json(['error' => 'Chat room pinning is not ready. Please run migrations.'], 500);
            }

            $existingPin = DB::table('chat_room_pins')
                ->where('chat_room_id', $chatRoom->id)
                ->where('user_id', $currentUser->id)
                ->exists();

            if ($existingPin) {
                DB::table('chat_room_pins')
                    ->where('chat_room_id', $chatRoom->id)
                    ->where('user_id', $currentUser->id)
                    ->delete();
            } else {
                $pinnedRoomCount = DB::table('chat_room_pins')
                    ->where('user_id', $currentUser->id)
                    ->count();

                if ($pinnedRoomCount >= 3) {
                    return response()->json([
                        'error' => 'You can only pin up to 3 chat rooms. Unpin one room before pinning another.'
                    ], 422);
                }

                DB::table('chat_room_pins')->insert([
                    'chat_room_id' => $chatRoom->id,
                    'user_id' => $currentUser->id,
                    'pinned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'is_pinned' => !$existingPin,
                'message' => $existingPin ? 'Chat room unpinned' : 'Chat room pinned',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling chat room pin', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to update chat room pin: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available users to add to chat room
     */
    public function getAvailableUsers(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            if (!$this->isChatAdmin($chatRoom, Auth::user())) {
                return response()->json(['error' => 'Only chat admins can add participants'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return response()->json([
                    'success' => true,
                    'users' => [],
                    'message' => 'Archived chats are read-only.',
                ]);
            }

            // Get users who are not already participants
            $existingParticipantIds = $chatRoom->participants()->pluck('users.id')->toArray();
            
            $availableUsers = User::whereNotIn('id', $existingParticipantIds)
                                 ->where('status', 'Verified') // Only verified users
                                 ->where('role', '!=', 'Admin')
                                 ->select('id', 'firstname', 'lastname', 'email', 'role')
                                 ->get()
                                 ->filter(fn (User $user) => $this->canBeAddedToChatRoom($chatRoom, $user))
                                 ->values()
                                 ->map(function ($user) {
                                     return [
                                         'id' => $user->id,
                                         'name' => $user->firstname . ' ' . $user->lastname,
                                         'email' => $user->email,
                                         'role' => $user->role
                                     ];
                                 });

            return response()->json([
                'success' => true,
                'users' => $availableUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting available users', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get available users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Leave a chat room
     */
    public function leaveChatRoom(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();
            
            // Check if user is participant
            if (!$chatRoom->hasParticipant($currentUser)) {
                return response()->json(['error' => 'You are not a participant in this chat room'], 400);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            if ($this->isChatAdmin($chatRoom, $currentUser)) {
                return response()->json([
                    'error' => 'Remove yourself as admin first and make sure another member is assigned as admin before leaving.'
                ], 422);
            }

            // Remove user from participants
            $chatRoom->participants()->detach($currentUser->id);
            $this->recordLeftChatRoom($chatRoom, $currentUser);

            // Add system message about user leaving
            ChatMessage::create([
                'chat_room_id' => $chatRoom->id,
                'user_id' => $currentUser->id,
                'message' => $currentUser->firstname . ' ' . $currentUser->lastname . ' left the chat',
                'message_type' => 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'You have left the chat room successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error leaving chat room', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to leave chat room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a chat room (creator/admin only)
     */
    public function deleteChatRoom(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            $currentUser = Auth::user();
            
            if (!$this->isChatAdmin($chatRoom, $currentUser)) {
                return response()->json(['error' => 'Only chat admins can delete this chat room'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            // Store room name for response
            $roomName = $chatRoom->name;

            // Delete all related data (messages, participants, etc.)
            // Laravel will handle cascade deletes based on foreign key constraints
            $chatRoom->delete();

            return response()->json([
                'success' => true,
                'message' => "Chat room '{$roomName}' has been deleted successfully"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting chat room', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete chat room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update typing status
     */
    public function updateTypingStatus(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            $validator = Validator::make($request->all(), [
                'is_typing' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            $isTyping = $request->is_typing;

            // Store typing status in cache (expires in 10 seconds)
            $cacheKey = "typing_status_{$chatRoom->id}_{$currentUser->id}";
            
            if ($isTyping) {
                cache()->put($cacheKey, [
                    'user_id' => $currentUser->id,
                    'user_name' => $currentUser->firstname . ' ' . $currentUser->lastname,
                    'timestamp' => now()
                ], 10); // 10 seconds
            } else {
                cache()->forget($cacheKey);
            }

            return response()->json([
                'success' => true,
                'message' => 'Typing status updated'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating typing status', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update typing status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get typing users for a chat room
     */
    public function getTypingUsers(ChatRoom $chatRoom): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return response()->json([
                    'success' => true,
                    'typing_users' => [],
                ]);
            }

            $currentUserId = Auth::id();
            $typingUsers = [];

            // Get all typing status cache keys for this room
            $cachePattern = "typing_status_{$chatRoom->id}_*";
            
            // Get participants to check typing status
            $participants = $chatRoom->participants()->get();
            
            foreach ($participants as $participant) {
                if ($participant->id !== $currentUserId) {
                    $cacheKey = "typing_status_{$chatRoom->id}_{$participant->id}";
                    $typingData = cache()->get($cacheKey);
                    
                    if ($typingData) {
                        $typingUsers[] = [
                            'user_id' => $participant->id,
                            'user_name' => $participant->firstname . ' ' . $participant->lastname
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'typing_users' => $typingUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting typing users', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get typing users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add or toggle a reaction to a message
     */
    public function toggleReaction(Request $request, ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            if ($this->isArchivedChatRoom($chatRoom)) {
                return $this->archivedChatResponse();
            }

            // Validate that message belongs to this chat room
            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            $validator = Validator::make($request->all(), [
                'emoji' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            $emoji = $request->emoji;

            // Check if user already reacted with this emoji
            $existingReaction = MessageReaction::where([
                'message_id' => $message->id,
                'user_id' => $currentUser->id,
                'emoji' => $emoji
            ])->first();

            if ($existingReaction) {
                // Remove reaction (toggle off)
                $existingReaction->delete();
                $action = 'removed';
            } else {
                // Add reaction (toggle on)
                MessageReaction::create([
                    'message_id' => $message->id,
                    'user_id' => $currentUser->id,
                    'emoji' => $emoji
                ]);
                $action = 'added';
            }

            // Get updated reactions summary
            $reactionsSummary = $message->getReactionsSummary();

            return response()->json([
                'success' => true,
                'action' => $action,
                'emoji' => $emoji,
                'reactions' => $reactionsSummary
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling reaction', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to toggle reaction: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get reactions for a specific message
     */
    public function getMessageReactions(ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        $this->abortIfWebsiteAdmin();

        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Validate that message belongs to this chat room
            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            $reactionsSummary = $message->getReactionsSummary();

            return response()->json([
                'success' => true,
                'reactions' => $reactionsSummary
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting message reactions', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get reactions: ' . $e->getMessage()], 500);
        }
    }
}
