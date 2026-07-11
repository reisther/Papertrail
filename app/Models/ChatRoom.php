<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatRoom extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'project_id',
        'created_by',
        'google_space_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the project that owns the chat room
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the chat room
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all messages for this chat room
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get all participants in this chat room
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants')
                    ->withPivot(['role', 'is_muted', 'last_read_at', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Get the latest message in this chat room
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest();
    }

    /**
     * Check if user is a participant in this chat room
     */
    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Add a participant to the chat room
     */
    public function addParticipant(User $user, string $role = 'member'): void
    {
        $existingParticipant = $this->participants()->where('user_id', $user->id)->first();

        if (!$existingParticipant) {
            $lastReadAt = null;

            if (\Illuminate\Support\Facades\Schema::hasTable('chat_participant_leaves')) {
                $lastReadAt = \Illuminate\Support\Facades\DB::table('chat_participant_leaves')
                    ->where('chat_room_id', $this->id)
                    ->where('user_id', $user->id)
                    ->value('left_at');
            }

            $this->participants()->attach($user->id, [
                'role' => $role,
                'joined_at' => now(),
                'last_read_at' => $lastReadAt,
            ]);

            return;
        }

        $rank = ['member' => 1, 'moderator' => 2, 'admin' => 3];
        $currentRole = $existingParticipant->pivot->role ?? 'member';

        if (($rank[$role] ?? 1) > ($rank[$currentRole] ?? 1)) {
            $this->participants()->updateExistingPivot($user->id, ['role' => $role]);
        }
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadCountForUser(User $user): int
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();
        
        if (!$participant) {
            return 0;
        }

        $lastReadAt = $participant->pivot->last_read_at;
        
        return $this->messages()
                    ->when($lastReadAt, function ($query) use ($lastReadAt) {
                        return $query->where('created_at', '>', $lastReadAt);
                    })
                    ->where('message_type', '!=', 'system')
                    ->where('user_id', '!=', $user->id)
                    ->count();
    }

    public function archivedRelationship(): ?AdviserStudent
    {
        if (! $this->project_id || ! Schema::hasColumn('adviser_student', 'archived_at')) {
            return null;
        }

        $this->loadMissing(['project.members', 'participants']);
        $project = $this->project;

        if (! $project) {
            return null;
        }

        $studentIds = collect([$project->owner_id])
            ->merge($project->members->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $participantIds = $this->participants->pluck('id')->filter()->unique()->values();

        if ($studentIds->isEmpty() || $participantIds->isEmpty()) {
            return null;
        }

        return AdviserStudent::query()
            ->approved()
            ->archived()
            ->whereIn('student_id', $studentIds)
            ->whereIn('adviser_id', $participantIds)
            ->latest('archived_at')
            ->first();
    }

    public function isArchived(): bool
    {
        return (bool) $this->archivedRelationship();
    }

    public static function archivedIdsForRoomIds($roomIds): Collection
    {
        $roomIds = collect($roomIds)
            ->filter()
            ->unique()
            ->values();

        if ($roomIds->isEmpty() || ! Schema::hasColumn('adviser_student', 'archived_at')) {
            return collect();
        }

        return DB::table('chat_rooms')
            ->join('projects', 'projects.id', '=', 'chat_rooms.project_id')
            ->whereIn('chat_rooms.id', $roomIds)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('adviser_student')
                    ->where('adviser_student.status', 'approved')
                    ->whereNotNull('adviser_student.archived_at')
                    ->where(function ($studentMatch) {
                        $studentMatch
                            ->whereColumn('adviser_student.student_id', 'projects.owner_id')
                            ->orWhereExists(function ($memberMatch) {
                                $memberMatch->select(DB::raw(1))
                                    ->from('project_members')
                                    ->whereColumn('project_members.project_id', 'projects.id')
                                    ->whereColumn('project_members.user_id', 'adviser_student.student_id');
                            });
                    })
                    ->whereExists(function ($participantMatch) {
                        $participantMatch->select(DB::raw(1))
                            ->from('chat_participants')
                            ->whereColumn('chat_participants.chat_room_id', 'chat_rooms.id')
                            ->whereColumn('chat_participants.user_id', 'adviser_student.adviser_id');
                    });
            })
            ->pluck('chat_rooms.id');
    }
}
