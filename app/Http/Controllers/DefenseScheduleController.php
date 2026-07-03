<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\User;
use App\Models\Project;
use App\Services\GoogleMeetService;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DefenseScheduleController extends Controller
{
    /**
     * Display the calendar view
     */
    public function index()
    {
        $user = Auth::user();
        
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
                  ->orWhereJsonContains('panel_members', $user->id);
            });
        }
        // Admin can see all schedules
        
        return view('defense-schedule.index');
    }

    /**
     * Get calendar events as JSON
     */
    public function getEvents(Request $request)
    {
        $user = Auth::user();
        
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
                  ->orWhereJsonContains('panel_members', $user->id);
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
                    'student' => $schedule->student->name,
                    'adviser' => $schedule->adviser->name,
                    'location' => $schedule->location,
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
     * Show form to create new defense schedule
     */
    public function create()
    {
        if (!Auth::user()->isTeacher() && !Auth::user()->canLeadGroup() && Auth::user()->role !== 'Admin') {
            abort(403, 'Only leaders, teachers, and admins can create defense schedules.');
        }
        
        $user = Auth::user();
        
        // Get students for this teacher
        $students = collect();
        if ($user->role === 'Teacher') {
            $studentIds = $user->students()->where('status', 'approved')->pluck('student_id');
            $students = User::whereIn('id', $studentIds)->orderBy('firstname')->get();
        } elseif ($user->canLeadGroup()) {
            $students = collect([$user]);
        } elseif ($user->role === 'Admin') {
            $students = User::whereIn('role', ['Student', 'Leader'])->orderBy('firstname')->get();
        }
        
        // Get all teachers for panel selection
        $teachers = User::where('role', 'Teacher')->orderBy('firstname')->get();
        
        return view('defense-schedule.create', compact('students', 'teachers'));
    }

    /**
     * Store new defense schedule
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isTeacher() && !Auth::user()->canLeadGroup() && Auth::user()->role !== 'Admin') {
            abort(403, 'Only leaders, teachers, and admins can create defense schedules.');
        }
        
        $normalizedMeetingLink = $this->normalizeMeetingLink($request->meeting_link);
        $request->merge(['meeting_link' => $normalizedMeetingLink]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'student_id' => 'required|exists:users,id',
            'adviser_id' => 'required|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:proposal,final,oral_exam',
            'panel_members' => 'nullable|array',
            'panel_members.*' => 'exists:users,id',
            'notes' => 'nullable|string',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'required|in:manual,google_meet,zoom,teams',
            'auto_create_meet' => 'nullable|boolean',
        ]);

        if (Auth::user()->canLeadGroup() && (int) $request->student_id !== Auth::id()) {
            abort(403, 'Leaders can only schedule for their own group.');
        }

        if (Auth::user()->canLeadGroup() && $request->project_id) {
            $project = Project::findOrFail($request->project_id);
            if ($project->owner_id !== Auth::id()) {
                abort(403, 'Leaders can only schedule their own group project.');
            }
        }
        
        // Prepare data for defense schedule
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => $request->student_id,
            'adviser_id' => $request->adviser_id,
            'project_id' => $request->project_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'type' => $request->type,
            'panel_members' => $request->panel_members,
            'notes' => $request->notes,
            'meeting_link' => $request->meeting_link,
            'meeting_platform' => $request->meeting_platform,
            'auto_create_meet' => $request->boolean('auto_create_meet'),
            'created_by' => Auth::id(),
        ];

        // Handle Google Meet integration
        if ($request->meeting_platform === 'google_meet' && $request->boolean('auto_create_meet')) {
            try {
                $googleMeetService = new GoogleMeetService();
                
                // Get attendee emails
                $attendees = [];
                if ($student = User::find($request->student_id)) {
                    $attendees[] = $student->email;
                }
                if ($adviser = User::find($request->adviser_id)) {
                    $attendees[] = $adviser->email;
                }
                if ($request->panel_members) {
                    $panelEmails = User::whereIn('id', $request->panel_members)
                        ->pluck('email')
                        ->filter()
                        ->toArray();
                    $attendees = array_merge($attendees, $panelEmails);
                }

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
                    ->with('error', 'Defense schedule created, but Google Meet integration failed. Please <a href="' . route('setup-google-auth') . '" class="underline text-blue-600">setup Google authorization</a> first, or manually add a meeting link after creating the schedule.');
            }
        }

        $defenseSchedule = DefenseSchedule::create($data);
        if ($defenseSchedule->usesGoogleMeet() && $defenseSchedule->meeting_link) {
            app(EmailNotificationService::class)->sendGoogleMeetCreated($defenseSchedule);
        }
        
        return redirect()->route('defense-schedule.index')
                        ->with('success', 'Defense schedule created successfully!');
    }

    /**
     * Show defense schedule details
     */
    public function show(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canView(Auth::user())) {
            abort(403, 'You do not have permission to view this defense schedule.');
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
            abort(403, 'You do not have permission to edit this defense schedule.');
        }
        
        $user = Auth::user();
        
        // Get students for this teacher
        $students = collect();
        if ($user->role === 'Teacher') {
            $studentIds = $user->students()->where('status', 'approved')->pluck('student_id');
            $students = User::whereIn('id', $studentIds)->orderBy('firstname')->get();
        } elseif ($user->canLeadGroup()) {
            $students = collect([$user]);
        } elseif ($user->role === 'Admin') {
            $students = User::whereIn('role', ['Student', 'Leader'])->orderBy('firstname')->get();
        }
        
        // Get all teachers for panel selection
        $teachers = User::where('role', 'Teacher')->orderBy('firstname')->get();
        
        // Get projects for the selected student
        $projects = Project::where('owner_id', $defenseSchedule->student_id)->get();
        
        return view('defense-schedule.edit', compact('defenseSchedule', 'students', 'teachers', 'projects'));
    }

    /**
     * Update defense schedule
     */
    public function update(Request $request, DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to edit this defense schedule.');
        }
        
        $normalizedMeetingLink = $this->normalizeMeetingLink($request->meeting_link);
        $request->merge(['meeting_link' => $normalizedMeetingLink]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'student_id' => 'required|exists:users,id',
            'adviser_id' => 'required|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,completed,cancelled,rescheduled',
            'type' => 'required|in:proposal,final,oral_exam',
            'panel_members' => 'nullable|array',
            'panel_members.*' => 'exists:users,id',
            'notes' => 'nullable|string',
            'meeting_link' => 'nullable|url',
        ]);

        if (Auth::user()->canLeadGroup() && (int) $request->student_id !== Auth::id()) {
            abort(403, 'Leaders can only update schedules for their own group.');
        }

        if (Auth::user()->canLeadGroup() && $request->project_id) {
            $project = Project::findOrFail($request->project_id);
            if ($project->owner_id !== Auth::id()) {
                abort(403, 'Leaders can only update their own group project schedule.');
            }
        }
        
        $defenseSchedule->update($request->all());
        
        return redirect()->route('defense-schedule.index')
                        ->with('success', 'Defense schedule updated successfully!');
    }

    /**
     * Delete defense schedule
     */
    public function destroy(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to delete this defense schedule.');
        }
        
        $defenseSchedule->delete();
        
        return redirect()->route('defense-schedule.index')
                        ->with('success', 'Defense schedule deleted successfully!');
    }

    /**
     * Get projects for a specific student (AJAX)
     */
    public function getStudentProjects(User $student)
    {
        $projects = Project::where('owner_id', $student->id)->get(['id', 'title']);
        return response()->json($projects);
    }


    /**
     * Create Google Meet for existing defense schedule
     */
    public function createGoogleMeet(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to modify this defense schedule.');
        }

        try {
            $googleMeetService = new GoogleMeetService();
            
            // Check if Google OAuth is properly setup
            if (!$googleMeetService->hasValidToken()) {
                return redirect()->back()->with('error', 'Google Meet integration is not setup. Please <a href="' . route('setup-google-auth') . '" class="underline text-blue-600">setup Google authorization</a> first.');
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

            // Update defense schedule with Google Meet information
            $defenseSchedule->update([
                'meeting_link' => $meetResult['meet_link'],
                'google_event_id' => $meetResult['event_id'],
                'google_calendar_link' => $meetResult['calendar_link'],
                'meeting_platform' => 'google_meet',
                'auto_create_meet' => true,
            ]);
            app(EmailNotificationService::class)->sendGoogleMeetCreated($defenseSchedule->fresh());

            return redirect()->back()->with('success', 'Google Meet created successfully! Calendar invites have been sent to all participants.');

        } catch (\Exception $e) {
            Log::error('Failed to create Google Meet for defense schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create Google Meet. Please <a href="' . route('setup-google-auth') . '" class="underline text-blue-600">setup Google authorization</a> first, or manually add a meeting link by editing the schedule.');
        }
    }

    /**
     * Update Google Meet for existing defense schedule
     */
    public function updateGoogleMeet(DefenseSchedule $defenseSchedule)
    {
        if (!$defenseSchedule->canEdit(Auth::user())) {
            abort(403, 'You do not have permission to modify this defense schedule.');
        }

        if (!$defenseSchedule->google_event_id) {
            return redirect()->back()->with('error', 'No Google Calendar event found to update.');
        }

        try {
            $googleMeetService = new GoogleMeetService();
            
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

            // Update defense schedule with new Google Meet information
            $defenseSchedule->update([
                'meeting_link' => $meetResult['meet_link'],
                'google_calendar_link' => $meetResult['calendar_link'],
            ]);

            return redirect()->back()->with('success', 'Google Meet updated successfully! Updated calendar invites have been sent to all participants.');

        } catch (\Exception $e) {
            Log::error('Failed to update Google Meet for defense schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update Google Meet. Please try again or contact support.');
        }
    }

    /**
     * Setup Google OAuth authorization
     */
    public function setupGoogleAuth()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can setup Google integration.');
        }

        try {
            $googleMeetService = new GoogleMeetService();
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
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can setup Google integration.');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('defense-schedule.index')->with('error', 'Google authorization was cancelled.');
        }

        try {
            $googleMeetService = new GoogleMeetService();
            $success = $googleMeetService->handleCallback($code);
            
            if ($success) {
                return redirect()->route('defense-schedule.index')->with('success', 'Google Meet integration setup successfully! You can now create Google Meet links.');
            } else {
                return redirect()->route('defense-schedule.index')->with('error', 'Failed to complete Google authorization.');
            }
        } catch (\Exception $e) {
            return redirect()->route('defense-schedule.index')->with('error', 'Google authorization failed: ' . $e->getMessage());
        }
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
            'proposal' => '#8b5cf6', // purple
            'final' => '#3b82f6',    // blue
            'oral_exam' => '#10b981', // green
            default => '#6b7280'      // gray
        };
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
