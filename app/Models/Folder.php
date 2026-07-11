<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    public const DEFAULT_PROJECT_FOLDERS = [
        [
            'name' => 'Concept Paper',
            'description' => 'Concept paper drafts and related documents.',
            'color' => '#7C3AED',
        ],
        [
            'name' => 'Chapter 1',
            'description' => 'Chapter 1 drafts and related documents.',
            'color' => '#2563EB',
        ],
        [
            'name' => 'Chapter 2',
            'description' => 'Chapter 2 drafts and related documents.',
            'color' => '#059669',
        ],
        [
            'name' => 'Chapter 3',
            'description' => 'Chapter 3 drafts and related documents.',
            'color' => '#D97706',
        ],
        [
            'name' => 'Chapter 4',
            'description' => 'Chapter 4 drafts and related documents.',
            'color' => '#DC2626',
        ],
        [
            'name' => 'Chapter 5',
            'description' => 'Chapter 5 drafts and related documents.',
            'color' => '#0891B2',
        ],
        [
            'name' => 'Final Manuscript',
            'description' => 'Final paper, appendices, and related completion documents.',
            'color' => '#4F46E5',
        ],
        [
            'name' => 'Submissions',
            'description' => 'Student work submissions for this project.',
            'color' => '#2563EB',
        ],
    ];

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'parent_id',
        'created_by',
        'color',
    ];

    /**
     * Get the project this folder belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get the user who created this folder
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get documents in this folder
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get all documents in this folder and subfolders
     */
    public function allDocuments()
    {
        $documents = $this->documents;
        
        foreach ($this->children as $child) {
            $documents = $documents->merge($child->allDocuments());
        }
        
        return $documents;
    }

    /**
     * Get folder path as breadcrumb array
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $current = $this;
        
        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumb;
    }

    /**
     * Get total size of all documents in folder and subfolders
     */
    public function getTotalSizeAttribute(): int
    {
        return $this->allDocuments()->sum('file_size');
    }

    /**
     * Get formatted total size
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
     * Check if user can access this folder
     */
    public function canAccess(User $user): bool
    {
        return $this->project->canAccess($user);
    }

    /**
     * Check if user can edit this folder
     */
    public function canEdit(User $user): bool
    {
        if ($this->project->isArchivedForUser($user)) {
            return false;
        }

        if ($this->isDefaultProjectFolder()) {
            return false;
        }

        // Only folder creator, project owner, or admin can edit folders
        // Teachers/advisers can view but not edit
        return $this->created_by === $user->id || 
               $this->project->owner_id === $user->id || 
               $user->role === 'Admin';
    }

    /**
     * Check if user can delete this folder
     */
    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function isSubmissionsFolder(): bool
    {
        return $this->parent_id === null && $this->name === 'Submissions';
    }

    public function isDefaultProjectFolder(): bool
    {
        return $this->parent_id === null
            && in_array($this->name, self::defaultProjectFolderNames(), true);
    }

    public static function defaultProjectFolderNames(): array
    {
        return array_column(self::DEFAULT_PROJECT_FOLDERS, 'name');
    }
}
