<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Project;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DefenseScheduleController extends Controller
{
    /**
     * Display the calendar view
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            abort(403, 'Admins do not have access to meeting schedules.');
        }
        
        // Get accessible schedules based on user role
        $query = DefenseSchedule::with(['student', 'adviser', 'project', 'creator']);
        
        if ($user->isStudentGroupRole()) {
            $query->where(function($q) use ($user) {
                $q->where('student_id', $user->id)
                  ->orWhereHas('project.members', function ($members) use ($user) {
                      $members->where('users.id', $user->id);
                  });
            });
        } elseif ($user->role === 'Teacher') {
            $query->where(function($q) use ($user) {
                $q->where('adviser_id', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhereHas('project', fn ($project) => $project->where('adviser_id', $user->id));
            });
        }
        return view('meeting-schedule.index');
    }

    /**
     * Get calendar events as JSON
     */
    public function getEvents(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            abort(403, 'Admins do not have access to meeting schedules.');
        }
        
        $query = DefenseSchedule::with(['student', 'adviser', 'project']);
        
        // Filter based on user role
        if ($user->isStudentGroupRole()) {
            $query->where(function($q) use ($user) {
                $q->where('student_id', $user->id)
                  ->orWhereHas('project.members', function ($members) use ($user) {
                      $members->where('users.id', $user->id);
                  });
            });
        } elseif ($user->role === 'Teacher') {
            $query->where(function($q) use ($user) {
                $q->where('adviser_id', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhereHas('project', fn ($project) => $project->where('adviser_id', $user->id));
            });
        }
        
        // Filter by date range if provided
        if ($request->has('start') && $request->has('end')) {
            $query->whereBetween('start_time', [
                Carbon::parse($request->start)->startOfDay(),
                Carbon::parse($request->end)->endOfDay()
            ]);
        }
        
        $schedules = $query->get();
        
        $events = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'start' => $schedule->start_time->toISOString(),
                'end' => $schedule->end_time->toISOString(),
                'backgroundColor' => $this->getEventColor($schedule),
                'borderColor' => $this->getEventColor($schedule),
                'extendedProps' => [
                    'description' => $schedule->description,
                    'student' => $schedule->student?->name,
                    'adviser' => $schedule->adviser?->name,
                    'location' => null,
                    'status' => $schedule->status,
                    'type' => $schedule->type,
                    'duration' => $schedule->duration,
                    'meeting_link' => $schedule->effective_meeting_link,
                    'meeting_platform' => 'manual',
                    'project_title' => $schedule->project?->title,
                ]
            ];
        });
        
        return response()->json($events);
    }

    /**
     * Show form to create a new meeting schedule
     */
    public function create()
    {
        if (!Auth::user()->isTeacher() && !Auth::user()->canLeadGroup()) {
            abort(403, 'Only leaders and advisers can create meetings.');
        }
        
        $user = Auth::user();
        
        $projects = collect();
        if ($user->role === 'Teacher') {
            $projects = Project::with(['owner', 'members'])
                ->where('adviser_id', $user->id)
                ->orderBy('title')
                ->get();
        } elseif ($user->canLeadGroup()) {
            $projects = $user->ownedProjects()
                ->with(['owner', 'members', 'adviser'])
                ->whereNotNull('adviser_id')
                ->orderBy('title')
                ->get();
        }
        
        return view('meeting-schedule.create', compact('projects'));
    }

    /**
     * Store a new meeting schedule
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isTeacher() && !Auth::user()->canLeadGroup()) {
            abort(403, 'Only leaders and advisers can create meetings.');
        }
        
        $normalizedMeetingLink = $this->normalizeMeetingLink($request->meeting_link);
        $request->merge(['meeting_link' => $normalizedMeetingLink]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'type' => 'required|in:meeting,consultation',
            'meeting_link' => 'nullable|url',
        ]);

        $project = Project::with(['owner', 'members', 'adviser'])->findOrFail($request->project_id);
        $user = Auth::user();

        if ($user->canLeadGroup()) {
            if ($project->owner_id !== Auth::id()) {
                abort(403, 'Leaders can only schedule their own group project.');
            }
        } elseif ($user->isTeacher()) {
            if ($project->adviser_id !== Auth::id()) {
                abort(403, 'Advisers can only schedule meetings for accepted student groups.');
            }
        } else {
            abort(403, 'Access denied.');
        }

        if (! $project->adviser_id) {
            return redirect()->back()->withInput()->with('error', 'This group does not have an accepted adviser yet.');
        }
        
        // Prepare data for meeting schedule.
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => $project->owner_id,
            'adviser_id' => $project->adviser_id,
            'project_id' => $project->id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => null,
            'type' => $request->type,
            'panel_members' => null,
            'notes' => null,
            'meeting_link' => $request->meeting_link,
            'meeting_platform' => 'manual',
            'auto_create_meet' => false,
            'created_by' => Auth::id(),
        ];

        $meetingSchedule = DefenseSchedule::create($data);
        $this->notifyMeetingParticipants($meetingSchedule->fresh(['project.owner', 'project.members', 'project.adviser']));

        $emailSentCount = 0;
        try {
            $emailSentCount = app(EmailNotificationService::class)
                ->sendMeetingScheduled($meetingSchedule->fresh(['student', 'adviser', 'project.owner', 'project.members', 'creator']));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send meeting schedule email notification.', [
                'meeting_schedule_id' => $meetingSchedule->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $message = $emailSentCount > 0
            ? 'Meeting scheduled successfully! Email notifications were sent.'
            : 'Meeting scheduled successfully, but no email notifications were sent. Please check participant emails and mail settings.';

        return redirect()->route('meeting-schedule.index')
                        ->with($emailSentCount > 0 ? 'success' : 'warning', $message);
    }

    /**
     * Show meeting schedule details
     */
    public function show(DefenseSchedule $meetingSchedule)
    {
        if (!$meetingSchedule->canView(Auth::user())) {
            abort(403, 'You do not have permission to view this meeting.');
        }
        
        $meetingSchedule->load(['student', 'adviser', 'project', 'creator']);
        
        return view('meeting-schedule.show', compact('meetingSchedule'));
    }

    /**
     * Show form to edit meeting schedule
     */
    public function edit(DefenseSchedule $meetingSchedule)
    {
        if (!$meetingSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to edit this meeting.');
        }
        
        $user = Auth::user();
        
        $projects = collect();
        if ($user->role === 'Teacher') {
            $projects = Project::with(['owner', 'members', 'adviser'])
                ->where('adviser_id', $user->id)
                ->orderBy('title')
                ->get();
        } elseif ($user->canLeadGroup()) {
            $projects = $user->ownedProjects()
                ->with(['owner', 'members', 'adviser'])
                ->whereNotNull('adviser_id')
                ->orderBy('title')
                ->get();
        }
        
        return view('meeting-schedule.edit', compact('meetingSchedule', 'projects'));
    }

    /**
     * Update meeting schedule
     */
    public function update(Request $request, DefenseSchedule $meetingSchedule)
    {
        if (!$meetingSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to edit this meeting.');
        }
        
        $normalizedMeetingLink = $this->normalizeMeetingLink($request->meeting_link);
        $request->merge(['meeting_link' => $normalizedMeetingLink]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'required|in:scheduled,completed,cancelled,rescheduled',
            'type' => 'required|in:meeting,consultation',
            'meeting_link' => 'nullable|url',
        ]);

        $project = Project::with(['owner', 'members', 'adviser'])->findOrFail($request->project_id);
        $user = Auth::user();

        if ($user->canLeadGroup()) {
            if ($project->owner_id !== Auth::id()) {
                abort(403, 'Leaders can only update their own group project schedule.');
            }
        } elseif ($user->isTeacher()) {
            if ($project->adviser_id !== Auth::id()) {
                abort(403, 'Advisers can only update meetings for accepted student groups.');
            }
        } else {
            abort(403, 'Access denied.');
        }
        
        $meetingSchedule->update([
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => $project->owner_id,
            'adviser_id' => $project->adviser_id,
            'project_id' => $project->id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => null,
            'status' => $request->status,
            'type' => $request->type,
            'panel_members' => null,
            'notes' => null,
            'meeting_link' => $request->meeting_link,
            'meeting_platform' => 'manual',
            'auto_create_meet' => false,
            'google_event_id' => null,
            'google_calendar_link' => null,
        ]);
        
        return redirect()->route('meeting-schedule.index')
                        ->with('success', 'Meeting updated successfully!');
    }

    /**
     * Delete meeting schedule
     */
    public function destroy(DefenseSchedule $meetingSchedule)
    {
        if (!$meetingSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to delete this meeting.');
        }
        
        $meetingSchedule->delete();
        
        return redirect()->route('meeting-schedule.index')
                        ->with('success', 'Meeting deleted successfully!');
    }

    /**
     * Get projects for a specific student (AJAX)
     */
    public function getStudentProjects(User $student)
    {
        if (!Auth::user()->isTeacher() && !Auth::user()->canLeadGroup()) {
            abort(403, 'Only leaders and advisers can load group projects.');
        }

        $projects = Project::where('owner_id', $student->id)->get(['id', 'title']);
        return response()->json($projects);
    }

    /**
     * Get event color based on type and status
     */
    private function getEventColor($schedule)
    {
        if ($schedule->status === 'cancelled') {
            return '#ef4444'; // red
        }
        
        return match($schedule->type) {
            'meeting' => '#3b82f6',
            'consultation' => '#10b981',
            default => '#6b7280'
        };
    }

    private function notifyMeetingParticipants(DefenseSchedule $schedule): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        $schedule->loadMissing(['project.owner', 'project.members', 'project.adviser']);

        $users = collect([$schedule->project?->owner, $schedule->project?->adviser])
            ->filter()
            ->merge($schedule->project?->members ?? collect())
            ->unique('id')
            ->reject(fn (User $user) => $user->id === Auth::id() || $user->isAdmin());

        foreach ($users as $user) {
            AppNotification::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'meeting_schedule',
                    'source_type' => 'meeting_schedule',
                    'source_id' => $schedule->id,
                ],
                [
                    'title' => 'Meeting scheduled',
                    'body' => $schedule->title . ' is scheduled for ' . $schedule->start_time->format('M j, Y g:i A') . '.',
                    'action_url' => route('meeting-schedule.show', $schedule),
                    'created_at' => $schedule->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function normalizeMeetingLink(?string $link): ?string
    {
        $link = trim((string) $link);

        if ($link === '') {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $link)) {
            return 'https://' . $link;
        }

        return $link;
    }

}
