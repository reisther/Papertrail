<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'title',
        'description',
        'group_course',
        'owner_id',
        'adviser_id',
        'status',
        'start_date',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Get the project owner (student)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the project adviser (teacher)
     */
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    /**
     * Get all documents in this project
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    /**
     * Get all folders in this project
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get member records for this project
     */
    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Get users who joined this project as members
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['role', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get invitation links for this project
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    /**
     * Get root folders (folders without parent)
     */
    public function rootFolders(): HasMany
    {
        return $this->hasMany(Folder::class)->whereNull('parent_id');
    }

    /**
     * Get documents not in any folder
     */
    public function rootDocuments(): HasMany
    {
        return $this->hasMany(Document::class)->whereNull('folder_id');
    }

    /**
     * Get total file size in bytes
     */
    public function getTotalSizeAttribute(): int
    {
        return $this->documents()->sum('file_size');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->getTotalSizeAttribute();
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if user can access this project
     */
    public function canAccess(User $user): bool
    {
        // Admin can access all projects
        if ($user->role === 'Admin') {
            return true;
        }

        // Project owner can access
        if ($this->owner_id === $user->id) {
            return true;
        }

        // Invited group members can access
        if ($this->members()->where('users.id', $user->id)->exists()) {
            return true;
        }

        // Direct adviser can access
        if ($this->adviser_id === $user->id) {
            return true;
        }

        // For teachers, check if they are an approved adviser of the project owner
        if ($user->role === 'Teacher') {
            $hasApprovedRelationship = $user->students()
                ->where('student_id', $this->owner_id)
                ->where('status', 'approved')
                ->exists();

            if (!$hasApprovedRelationship) {
                $memberIds = $this->members()->pluck('users.id');

                $hasApprovedRelationship = $user->students()
                    ->whereIn('student_id', $memberIds)
                    ->where('status', 'approved')
                    ->exists();
            }

            if ($hasApprovedRelationship) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can edit this project
     */
    public function canEdit(User $user): bool
    {
        return ($this->owner_id === $user->id && $user->canLeadGroup()) || $user->role === 'Admin';
    }

    /**
     * Check if user can invite members to this project
     */
    public function canInviteMembers(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function syncCourseTasksFromAdviser(): int
    {
        $course = $this->group_course ?: $this->owner?->course;

        if (! $this->adviser_id || ! $course) {
            return 0;
        }

        $templateTasks = ProjectTask::query()
            ->where('adviser_id', $this->adviser_id)
            ->where('assignment_course', $course)
            ->where('project_id', '!=', $this->id)
            ->orderBy('chapter')
            ->orderBy('created_at')
            ->get()
            ->unique(fn (ProjectTask $task) => $task->course_task_group_id ?: "{$task->chapter}|{$task->title}");

        $createdCount = 0;

        foreach ($templateTasks as $templateTask) {
            $existingTaskQuery = $this->tasks()
                ->where('adviser_id', $this->adviser_id)
                ->where('chapter', $templateTask->chapter);

            if ($templateTask->course_task_group_id) {
                $existingTaskQuery->where('course_task_group_id', $templateTask->course_task_group_id);
            } else {
                $existingTaskQuery
                    ->where('assignment_course', $course)
                    ->where('title', $templateTask->title);
            }

            if ($existingTaskQuery->exists()) {
                continue;
            }

            $this->tasks()->create([
                'adviser_id' => $this->adviser_id,
                'assignment_course' => $course,
                'course_task_group_id' => $templateTask->course_task_group_id,
                'chapter' => $templateTask->chapter,
                'title' => $templateTask->title,
            ]);

            $createdCount++;
        }

        return $createdCount;
    }
}
