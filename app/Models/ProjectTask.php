<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    public const STAGES = [
        0 => 'Concept Paper',
        1 => 'Chapter 1',
        2 => 'Chapter 2',
        3 => 'Chapter 3',
        4 => 'Chapter 4',
        5 => 'Chapter 5',
    ];

    protected $fillable = [
        'project_id',
        'adviser_id',
        'assignment_course',
        'course_task_group_id',
        'chapter',
        'title',
        'is_completed',
        'completed_at',
        'completion_note',
        'completion_contributors',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'completion_contributors' => 'array',
    ];

    public function getCompletionNamesAttribute(): ?string
    {
        return $this->completion_contributors
            ? implode(', ', $this->completion_contributors)
            : $this->completion_note;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest('id');
    }
}
