<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviserStudent extends Model
{
    protected $table = 'adviser_student';
    
    protected $fillable = [
        'student_id',
        'adviser_id',
        'status',
        'message',
        'response_message',
        'requested_at',
        'responded_at',
        'archived_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the student who made the request
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the adviser who received the request
     */
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for active adviser/student relationships.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope for archived adviser/student relationships.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope for requests that should block another adviser request.
     */
    public function scopeCurrentRequest($query)
    {
        return $query->where(function ($currentRequest) {
            $currentRequest->where('status', 'pending')
                ->orWhere(function ($approvedActive) {
                    $approvedActive->where('status', 'approved')
                        ->whereNull('archived_at');
                });
        });
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
