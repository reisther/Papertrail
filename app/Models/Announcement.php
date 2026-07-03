<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'audience_type',
        'project_id',
        'message',
        'attachment_path',
        'attachment_name',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! Schema::hasColumn('announcements', 'audience_type')) {
            return $query;
        }

        $groupProjectIds = $user->canLeadGroup()
            ? $user->ownedProjects()->pluck('id')
            : $user->joinedProjects()->pluck('projects.id');

        $groupLeaderIds = collect([$user->id]);

        if ($user->isStudent()) {
            $groupLeaderIds = $groupLeaderIds
                ->merge($user->joinedProjects()->pluck('owner_id'));
        }

        $activeAdviserIds = AdviserStudent::query()
            ->approved()
            ->active()
            ->whereIn('student_id', $groupLeaderIds->unique()->values())
            ->pluck('adviser_id');

        return $query->where(function (Builder $visible) use ($user, $groupProjectIds, $activeAdviserIds) {
            $visible->where('audience_type', 'global')
                ->orWhere('user_id', $user->id)
                ->orWhere(function (Builder $projectAudience) use ($groupProjectIds) {
                    $projectAudience->where('audience_type', 'project')
                        ->whereIn('project_id', $groupProjectIds);
                })
                ->orWhere(function (Builder $adviserAudience) use ($activeAdviserIds) {
                    $adviserAudience->where('audience_type', 'adviser_students')
                        ->whereIn('user_id', $activeAdviserIds);
                });
        });
    }

    public function isManageableBy(User $user): bool
    {
        return $this->user_id === $user->id || ($user->isAdmin() && $this->audience_type === 'global');
    }
}
