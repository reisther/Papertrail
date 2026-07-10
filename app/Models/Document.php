<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'name',
        'original_name',
        'description',
        'file_path',
        'mime_type',
        'file_size',
        'project_id',
        'folder_id',
        'uploaded_by',
        'is_shared',
        'permissions',
        'last_accessed',
        'download_count',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_shared' => 'boolean',
        'last_accessed' => 'datetime',
    ];

    /**
     * Get the project this document belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the folder this document is in
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the user who uploaded this document
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if file is a Word document
     */
    public function isWordDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    /**
     * Get file icon based on type
     */
    public function getIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'image';
        } elseif ($this->isPdf()) {
            return 'pdf';
        } elseif ($this->isWordDocument()) {
            return 'word';
        } else {
            return 'file';
        }
    }

    /**
     * Get file icon color
     */
    public function getIconColorAttribute(): string
    {
        if ($this->isImage()) {
            return 'text-green-600';
        } elseif ($this->isPdf()) {
            return 'text-red-600';
        } elseif ($this->isWordDocument()) {
            return 'text-blue-600';
        } else {
            return 'text-gray-600';
        }
    }

    /**
     * Check if file exists in storage
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get full file path
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/public/' . $this->file_path);
    }

    /**
     * Update last accessed timestamp
     */
    public function markAsAccessed(): void
    {
        $this->update([
            'last_accessed' => now(),
            'download_count' => $this->download_count + 1
        ]);
    }

    /**
     * Check if user can access this document
     */
    public function canAccess(User $user): bool
    {
        // Check project access first
        if (!$this->project->canAccess($user)) {
            return false;
        }

        // If document is shared, check permissions
        if ($this->is_shared && $this->permissions) {
            return in_array($user->id, $this->permissions['users'] ?? []) ||
                   in_array($user->role, $this->permissions['roles'] ?? []);
        }

        return true;
    }

    /**
     * Check if user can edit this document
     */
    public function canEdit(User $user): bool
    {
        if ($this->project->isArchivedForUser($user)) {
            return false;
        }

        // Only document uploader, project owner, or admin can edit documents
        // Teachers/advisers can view and download but not edit
        return $this->uploaded_by === $user->id || 
               $this->project->owner_id === $user->id || 
               $user->role === 'Admin';
    }

    /**
     * Check if user can delete this document
     */
    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    /**
     * Check if user can download this document
     */
    public function canDownload(User $user): bool
    {
        // All users with project access can download documents
        return $this->canAccess($user);
    }

    /**
     * Check if user can preview this document
     */
    public function canPreview(User $user): bool
    {
        // All users with project access can preview documents
        return $this->canAccess($user);
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            if ($document->fileExists()) {
                Storage::disk('public')->delete($document->file_path);
            }
        });
    }
}
