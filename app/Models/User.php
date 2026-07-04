<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\AdviserExpertise;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'campus',
        'course',
        'section',
        'student_number',
        'profile_picture_path',
        'adviser_schedule_path',
        'adviser_schedule_name',
        'adviser_schedule_mime',
        'id_document_path',
        'status',
        'email',
        'role',
        'password',
        'verified_at',
        'verified_by',
        'admin_notes',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim($this->firstname . ' ' . ($this->middlename ? $this->middlename . ' ' : '') . $this->lastname);
    }

    /**
     * Get adviser requests where this user is the student
     */
    public function adviserRequests(): HasMany
    {
        return $this->hasMany(AdviserStudent::class, 'student_id');
    }

    /**
     * Get student requests where this user is the adviser
     */
    public function studentRequests(): HasMany
    {
        return $this->hasMany(AdviserStudent::class, 'adviser_id');
    }

    /**
     * Get approved advisers for this student
     */
    public function advisers(): HasMany
    {
        return $this->hasMany(AdviserStudent::class, 'student_id')->approved();
    }

    /**
     * Get approved students for this adviser
     */
    public function students(): HasMany
    {
        return $this->hasMany(AdviserStudent::class, 'adviser_id')->approved();
    }

    /**
     * Check if user is a teacher/adviser
     */
    public function isTeacher(): bool
    {
        return $this->role === 'Teacher';
    }

    /**
     * Check if user is a leader
     */
    public function isLeader(): bool
    {
        return $this->role === 'Leader';
    }

    /**
     * Check if user can initiate group-level student workflows
     */
    public function canLeadGroup(): bool
    {
        return $this->isLeader();
    }

    /**
    * Get the expertise record of the adviser (User).
    */
    public function expertise()
    {
    return $this->hasOne(AdviserExpertise::class, 'adviser_id');
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->role === 'Student';
    }

    /**
     * Check if user belongs to a student group role
     */
    public function isStudentGroupRole(): bool
    {
        return in_array($this->role, ['Student', 'Leader']);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Keep the role-specific profile tables aligned with the user's role.
     */
    public function syncRoleProfile(): void
    {
        $tables = ['students', 'advisers', 'admins', 'leaders'];

        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)
                ->where('user_id', $this->id)
                ->delete();
        }

        $table = match ($this->role) {
            'Student' => 'students',
            'Teacher' => 'advisers',
            'Admin' => 'admins',
            'Leader' => 'leaders',
            default => null,
        };

        if ($table) {
            \Illuminate\Support\Facades\DB::table($table)->updateOrInsert(
                ['user_id' => $this->id],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Get the admin who verified this user
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get chat rooms where user is a participant
     */
    public function chatRooms()
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_participants')
                    ->withPivot(['role', 'is_muted', 'last_read_at', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Get chat messages sent by this user
     */
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the admin who rejected this user
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Check if user has uploaded documents
     */
    public function hasDocument(): bool
    {
        return !empty($this->id_document_path);
    }

    /**
     * Get document file extension
     */
    public function getDocumentExtension(): ?string
    {
        if (!$this->id_document_path) {
            return null;
        }
        return pathinfo($this->id_document_path, PATHINFO_EXTENSION);
    }

    /**
     * Check if document is an image
     */
    public function isDocumentImage(): bool
    {
        $extension = $this->getDocumentExtension();
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    /**
     * Check if document is a PDF
     */
    public function isDocumentPdf(): bool
    {
        return strtolower($this->getDocumentExtension()) === 'pdf';
    }

    /**
     * Get projects owned by this user
     */
    public function ownedProjects()
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * Get projects where this user is an adviser
     */
    public function advisedProjects()
    {
        return $this->hasMany(Project::class, 'adviser_id');
    }

    /**
     * Get project membership records for this user
     */
    public function projectMemberships()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Get projects this user joined through an invite link
     */
    public function joinedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
                    ->withPivot(['role', 'invited_by', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Get all projects this user has access to
     */
    public function accessibleProjects()
    {
        if ($this->role === 'Admin') {
            return Project::query();
        }

        $query = Project::where('owner_id', $this->id)
            ->orWhere('adviser_id', $this->id)
            ->orWhereHas('members', function ($members) {
                $members->where('users.id', $this->id);
            });

        // For teachers, also include projects from students they advise
        if ($this->role === 'Teacher') {
            $studentIds = $this->students()
                ->where('status', 'approved')
                ->pluck('student_id');
            
            if ($studentIds->isNotEmpty()) {
                $query->orWhereIn('owner_id', $studentIds)
                    ->orWhereHas('members', function ($members) use ($studentIds) {
                        $members->whereIn('users.id', $studentIds);
                    });
            }
        }

        return $query;
    }

    /**
     * Get documents uploaded by this user
     */
    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * Get folders created by this user
     */
    public function createdFolders()
    {
        return $this->hasMany(Folder::class, 'created_by');
    }
}
