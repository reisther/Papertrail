<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Project;
use App\Services\GoogleMeetService;
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
        return view('defense-schedule.index');
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
                    'meeting_platform' => $schedule->meeting_platform,
                    'google_calendar_link' => $schedule->google_calendar_link,
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
        
        return view('defense-schedule.create', compact('projects'));
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
            'meeting_platform' => 'required|in:manual,google_meet,zoom,teams',
            'auto_create_meet' => 'nullable|boolean',
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
            'meeting_platform' => $request->meeting_platform,
            'auto_create_meet' => $request->meeting_platform === 'google_meet',
            'created_by' => Auth::id(),
        ];

        // Handle Google Meet integration
        if ($request->meeting_platform === 'google_meet') {
            try {
                $googleMeetService = new GoogleMeetService(Auth::user());
                if (!$googleMeetService->hasValidToken()) {
                    return redirect()->back()->withInput()->with('error', 'Connect your Google Calendar first, or choose Manual Link Entry for now.');
                }
                
                $attendees = $this->projectMeetingAttendees($project);

                // Create Google Meet event
                $meetResult = $googleMeetService->createMeetingEvent(
                    $request->title,
                    $request->description,
                    $request->start_time,
                    $request->end_time,
                    array_unique($attendees)
                );

                // Update data with Google Meet information
                $data['meeting_link'] = $meetResult['meet_link'];
                $data['google_event_id'] = $meetResult['event_id'];
                $data['google_calendar_link'] = $meetResult['calendar_link'];

            } catch (\Exception $e) {
                Log::error('Failed to create Google Meet event: ' . $e->getMessage());
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', $this->googleMeetFailureMessage($e));
            }
        }

        $defenseSchedule = DefenseSchedule::create($data);
        $this->notifyMeetingParticipants($defenseSchedule->fresh(['project.owner', 'project.members', 'project.adviser']));

        try {
            app(EmailNotificationService::class)->sendMeetingScheduled($defenseSchedule->fresh(['student', 'adviser', 'project.owner', 'project.members', 'creator']));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send meeting schedule email notification.', [
                'defense_schedule_id' => $defenseSchedule->id,
                'error' => $exception->getMessage(),
            ]);
        }
        
        return redirect()->route('defense-schedule.index')
                        ->with('success', 'Meeting scheduled successfully!');
    }

    /**
     * Show defense schedule details
     */
    public function show(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canView(Auth::user())) {
            abort(403, 'You do not have permission to view this meeting.');
        }
        
        $defenseSchedule->load(['student', 'adviser', 'project', 'creator']);
        
        return view('defense-schedule.show', compact('defenseSchedule'));
    }

    /**
     * Show form to edit defense schedule
     */
    public function edit(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
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
        
        return view('defense-schedule.edit', compact('defenseSchedule', 'projects'));
    }

    /**
     * Update defense schedule
     */
    public function update(Request $request, DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
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
        
        $defenseSchedule->update([
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
        ]);
        
        return redirect()->route('defense-schedule.index')
                        ->with('success', 'Meeting updated successfully!');
    }

    /**
     * Delete defense schedule
     */
    public function destroy(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to delete this meeting.');
        }
        
        $defenseSchedule->delete();
        
        return redirect()->route('defense-schedule.index')
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
     * Create Google Meet for existing defense schedule
     */
    public function createGoogleMeet(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to modify this meeting.');
        }

        try {
            $googleMeetService = new GoogleMeetService(Auth::user());
            
            // Check if Google OAuth is properly setup
            if (!$googleMeetService->hasValidToken()) {
                return redirect()->back()->with('error', 'Connect your Google Calendar first. <a href="' . route('setup-google-auth') . '" class="underline text-blue-600">Connect Google Calendar</a>');
            }
            
            // Get attendee emails
            $attendees = $defenseSchedule->getAttendeeEmails();

            // Create Google Meet event
            $meetResult = $googleMeetService->createMeetingEvent(
                $defenseSchedule->title,
                $defenseSchedule->description,
                $defenseSchedule->start_time,
                $defenseSchedule->end_time,
                $attendees
            );

            // Update meeting schedule with Google Meet information.
            $defenseSchedule->update([
                'meeting_link' => $meetResult['meet_link'],
                'google_event_id' => $meetResult['event_id'],
                'google_calendar_link' => $meetResult['calendar_link'],
                'meeting_platform' => 'google_meet',
                'auto_create_meet' => true,
            ]);
            try {
                app(EmailNotificationService::class)->sendGoogleMeetCreated($defenseSchedule->fresh());
            } catch (\Throwable $exception) {
                Log::warning('Failed to send Google Meet email notification.', [
                    'defense_schedule_id' => $defenseSchedule->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            return redirect()->back()->with('success', 'Google Meet created successfully! Calendar invites have been sent to all participants.');

        } catch (\Exception $e) {
            Log::error('Failed to create Google Meet for meeting schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', $this->googleMeetFailureMessage($e));
        }
    }

    /**
     * Update Google Meet for existing defense schedule
     */
    public function updateGoogleMeet(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to modify this meeting.');
        }

        if (!$defenseSchedule->google_event_id) {
            return redirect()->back()->with('error', 'No Google Calendar event found to update.');
        }

        try {
            $googleMeetService = new GoogleMeetService(Auth::user());
            if (!$googleMeetService->hasValidToken()) {
                return redirect()->back()->with('error', 'Connect your Google Calendar first before updating this Google Meet event.');
            }
            
            // Get attendee emails
            $attendees = $defenseSchedule->getAttendeeEmails();

            // Update Google Meet event
            $meetResult = $googleMeetService->updateMeetingEvent(
                $defenseSchedule->google_event_id,
                $defenseSchedule->title,
                $defenseSchedule->description,
                $defenseSchedule->start_time,
                $defenseSchedule->end_time,
                $attendees
            );

            // Update meeting schedule with new Google Meet information.
            $defenseSchedule->update([
                'meeting_link' => $meetResult['meet_link'],
                'google_calendar_link' => $meetResult['calendar_link'],
            ]);

            return redirect()->back()->with('success', 'Google Meet updated successfully! Updated calendar invites have been sent to all participants.');

        } catch (\Exception $e) {
            Log::error('Failed to update Google Meet for meeting schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update Google Meet. Please try again or contact support.');
        }
    }

    /**
     * Setup Google OAuth authorization
     */
    public function setupGoogleAuth()
    {
        if (! $this->canConnectGoogleCalendar(Auth::user())) {
            abort(403, 'Only advisers and group leaders can connect Google Calendar.');
        }

        try {
            $googleMeetService = new GoogleMeetService(Auth::user());
            $authUrl = $googleMeetService->getAuthUrl();
            
            return redirect($authUrl);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to setup Google authorization: ' . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        if (! $this->canConnectGoogleCalendar(Auth::user())) {
            abort(403, 'Only advisers and group leaders can connect Google Calendar.');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('defense-schedule.index')->with('error', 'Google authorization was cancelled.');
        }

        try {
            $googleMeetService = new GoogleMeetService(Auth::user());
            $success = $googleMeetService->handleCallback($code);
            
            if ($success) {
                return redirect()->route('profile.edit')->with('status', 'Google Calendar connected successfully. You can now create Google Meet links from your account.');
            } else {
                return redirect()->route('profile.edit')->with('error', 'Failed to complete Google authorization.');
            }
        } catch (\Exception $e) {
            return redirect()->route('profile.edit')->with('error', 'Google authorization failed: ' . $e->getMessage());
        }
    }

    public function disconnectGoogleAuth()
    {
        if (! $this->canConnectGoogleCalendar(Auth::user())) {
            abort(403, 'Only advisers and group leaders can disconnect Google Calendar.');
        }

        if (Schema::hasTable('google_oauth_tokens')) {
            $query = \Illuminate\Support\Facades\DB::table('google_oauth_tokens')
                ->where('provider', 'google_calendar');

            if (Schema::hasColumn('google_oauth_tokens', 'user_id')) {
                $query->where('user_id', Auth::id());
            }

            $query->delete();
        }

        return redirect()->route('profile.edit')->with('status', 'Google Calendar disconnected.');
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

    private function projectMeetingAttendees(Project $project): array
    {
        $project->loadMissing(['owner', 'members', 'adviser']);

        return collect([$project->owner, $project->adviser])
            ->filter()
            ->merge($project->members)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
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
                    'source_type' => 'defense_schedule',
                    'source_id' => $schedule->id,
                ],
                [
                    'title' => 'Meeting scheduled',
                    'body' => $schedule->title . ' is scheduled for ' . $schedule->start_time->format('M j, Y g:i A') . '.',
                    'action_url' => route('defense-schedule.show', $schedule),
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

    private function googleMeetFailureMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'SERVICE_DISABLED') || str_contains($message, 'calendar-json.googleapis.com')) {
            return 'Google Meet could not be created because Google Calendar API is disabled in your Google Cloud project. Enable Google Calendar API, wait a few minutes, then try again.';
        }

        if (str_contains($message, 'invalid_grant')) {
            return 'Google Meet authorization expired or was changed. Ask an admin to setup Google authorization again.';
        }

        if (str_contains($message, 'insufficient') || str_contains($message, 'PERMISSION_DENIED')) {
            return 'Google Meet could not be created because the authorized Gmail does not have enough Calendar permission. Ask an admin to authorize Google again and allow Calendar access.';
        }

        return 'Google Meet could not be created. Please try again, or choose Manual Link Entry for now.';
    }

    private function canConnectGoogleCalendar(?User $user): bool
    {
        return $user && ($user->isTeacher() || $user->canLeadGroup());
    }
}
