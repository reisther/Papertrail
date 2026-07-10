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
                $chatRoomIds = DB::table('chat_participants')
                    ->join('chat_rooms', 'chat_rooms.id', '=', 'chat_participants.chat_room_id')
                    ->where('chat_participants.user_id', $user->id)
                    ->where('chat_rooms.is_active', true)
                    ->pluck('chat_rooms.id');

                $archivedChatRoomIds = ChatRoom::archivedIdsForRoomIds($chatRoomIds);
                $activeChatRoomIds = $chatRoomIds
                    ->reject(fn ($roomId) => $archivedChatRoomIds->contains($roomId))
                    ->values();

                $chatUnreadCount = $activeChatRoomIds->isEmpty()
                    ? 0
                    : ChatMessage::query()
                        ->join('chat_participants', function ($join) use ($user) {
                            $join->on('chat_participants.chat_room_id', '=', 'chat_messages.chat_room_id')
                                ->where('chat_participants.user_id', '=', $user->id);
                        })
                        ->whereIn('chat_messages.chat_room_id', $activeChatRoomIds)
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
