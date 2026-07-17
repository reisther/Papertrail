<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefenseSchedule extends Model
{
    protected $table = 'meeting_schedules';

    protected $fillable = [
        'title',
        'description',
        'student_id',
        'adviser_id',
        'project_id',
        'start_time',
        'end_time',
        'location',
        'status',
        'panel_members',
        'notes',
        'meeting_link',
        'google_event_id',
        'google_calendar_link',
        'auto_create_meet',
        'meeting_platform',
        'type',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'panel_members' => 'array',
        'auto_create_meet' => 'boolean',
    ];

    /**
     * Get the student for this meeting
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the adviser for this meeting
     */
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    /**
     * Get the project for this meeting
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this schedule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get panel members
     */
    public function getPanelMembersUsersAttribute()
    {
        if (!$this->panel_members) {
            return collect();
        }
        
        return User::whereIn('id', $this->panel_members)->get();
    }

    /**
     * Check if user can view this meeting schedule
     */
    public function canView(User $user): bool
    {
        return $this->student_id === $user->id ||
               $this->adviser_id === $user->id ||
               $this->created_by === $user->id ||
               ($this->project && $this->project->adviser_id === $user->id) ||
               ($this->project && $this->project->members()->where('users.id', $user->id)->exists());
    }

    /**
     * Check if user can edit this meeting schedule
     */
    public function canEdit(User $user): bool
    {
        return $this->adviser_id === $user->id ||
               $this->created_by === $user->id ||
               ($this->project && $this->project->owner_id === $user->id && $user->canLeadGroup());
    }

    /**
     * Get formatted duration
     */
    public function getDurationAttribute(): string
    {
        $minutes = $this->start_time->diffInMinutes($this->end_time);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }
        
        return "{$remainingMinutes}m";
    }

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            'rescheduled' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get type color for display
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'meeting' => 'blue',
            'consultation' => 'green',
            default => 'gray'
        };
    }

    /**
     * Get the meeting link.
     */
    public function getEffectiveMeetingLinkAttribute(): ?string
    {
        return $this->meeting_link;
    }
}
