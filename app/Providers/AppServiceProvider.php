<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\AppNotification;
use App\Models\ChatMessage;
use App\Models\ChatRoom;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.navigation', function ($view) {
            $counts = [
                'chatUnreadCount' => 0,
                'notificationUnreadCount' => 0,
                'studentRequestPendingCount' => 0,
            ];

            if (! Auth::check()) {
                $view->with($counts);

                return;
            }

            $user = Auth::user();
            $cacheKey = "navigation-counts:{$user->id}";

            $counts = Cache::remember($cacheKey, now()->addSeconds(15), function () use ($user) {
                $accessibleProjectIds = $user->accessibleProjects()->pluck('id');
                $leaveTrackingEnabled = Schema::hasTable('chat_participant_leaves');

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
                    ->with('project')
                    ->get()
                    ->unique('id')
                    ->values();

                $archivedChatRoomIds = ChatRoom::archivedIdsForRoomIds($chatRooms->pluck('id'));
                $visibleActiveChatRoomIds = $chatRooms
                    ->filter(function (ChatRoom $room) use ($user, $archivedChatRoomIds) {
                        $isArchived = $archivedChatRoomIds->contains($room->id);

                        if ($isArchived) {
                            $archivedRelationship = $room->archivedRelationship();

                            if ($archivedRelationship) {
                                $project = $room->project;
                                $canViewArchivedRoom = (int) $archivedRelationship->student_id === (int) $user->id
                                    || (int) $archivedRelationship->adviser_id === (int) $user->id
                                    || (int) ($project?->owner_id) === (int) $user->id
                                    || (bool) ($project && $project->members()->where('users.id', $user->id)->exists());

                                if (! $canViewArchivedRoom) {
                                    return false;
                                }
                            }
                        }

                        return $user->isStudentGroupRole() || ! $isArchived;
                    })
                    ->pluck('id')
                    ->values();

                $chatUnreadCount = $visibleActiveChatRoomIds->isEmpty()
                    ? 0
                    : ChatMessage::query()
                        ->join('chat_participants', function ($join) use ($user) {
                            $join->on('chat_participants.chat_room_id', '=', 'chat_messages.chat_room_id')
                                ->where('chat_participants.user_id', '=', $user->id);
                        })
                        ->whereIn('chat_messages.chat_room_id', $visibleActiveChatRoomIds)
                        ->where('chat_messages.message_type', '!=', 'system')
                        ->where('chat_messages.user_id', '!=', $user->id)
                        ->where(function ($query) {
                            $query->whereNull('chat_participants.last_read_at')
                                ->orWhereColumn('chat_messages.created_at', '>', 'chat_participants.last_read_at');
                        })
                        ->count();

                $studentRequestPendingCount = $user->isTeacher()
                    ? $user->studentRequests()->where('status', 'pending')->count()
                    : 0;

                $notificationUnreadCount = $studentRequestPendingCount;
                if (Schema::hasTable('app_notifications')) {
                    $notificationUnreadCount = AppNotification::where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->count();

                    if ($archivedChatRoomIds->isNotEmpty()) {
                        $archivedChatRoomJsonIds = $archivedChatRoomIds
                            ->flatMap(fn ($id) => [(int) $id, (string) $id])
                            ->unique()
                            ->values()
                            ->all();

                        $notificationUnreadCount -= AppNotification::where('user_id', $user->id)
                            ->whereNull('read_at')
                            ->where('type', 'chat_mention')
                            ->whereIn('data->chat_room_id', $archivedChatRoomJsonIds)
                            ->count();
                    }
                }

                return [
                    'chatUnreadCount' => max(0, $chatUnreadCount),
                    'notificationUnreadCount' => max(0, $notificationUnreadCount),
                    'studentRequestPendingCount' => $studentRequestPendingCount,
                ];
            });

            $view->with($counts);
        });
    }
}
