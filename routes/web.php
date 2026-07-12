<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdviserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DefenseScheduleController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Models\AppNotification;
use App\Models\DefenseSchedule;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Http\Controllers\TitleSubmissionController;
use App\Http\Controllers\SuggestedAIController;

$manuscriptStages = fn () => collect([
    0 => 'Concept Paper',
    1 => 'Chapter 1',
    2 => 'Chapter 2',
    3 => 'Chapter 3',
    4 => 'Chapter 4',
    5 => 'Chapter 5',
]);

Route::get('/', function () {
    return view('papertrail-landing');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/registration-success', function () {
    return view('registration-success');
})->name('registration.success');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Admin Dashboard
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth'])->name('admin.dashboard');

// Teacher Dashboard
Route::get('/teacher/dashboard', function () {
    return view('teacher.dashboard');
})->middleware(['auth'])->name('teacher.dashboard');

// Test route to check authentication
Route::get('/auth-test', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return response()->json([
            'authenticated' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status
            ]
        ]);
    }
    return response()->json(['authenticated' => false]);
});

// Test dashboard without middleware
Route::get('/dashboard-test', function () {
    if (Auth::check()) {
        return view('dashboard');
    }
    return 'Not authenticated';
});

// Direct login test
Route::get('/login-test', function () {
    $user = \App\Models\User::where('email', 'student@papertrail.com')->first();
    if ($user) {
        Auth::login($user);
        return redirect(route('dashboard'));
    }
    return 'User not found';
});

Route::middleware('auth')->group(function () use ($manuscriptStages) {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile-picture/{user}', [ProfileController::class, 'picture'])->name('profile.picture');
    Route::get('/adviser-schedule/{user}', [ProfileController::class, 'adviserSchedule'])->name('profile.adviser-schedule');
    Route::delete('/adviser-schedule', [ProfileController::class, 'destroyAdviserSchedule'])->name('profile.adviser-schedule.destroy');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', function () {
        $user = Auth::user();

        if (! Schema::hasTable('app_notifications')) {
            return view('notifications.index', [
                'chatNotifications' => collect(),
                'announcementNotifications' => collect(),
                'studentRequestNotifications' => collect(),
                'meetingNotifications' => collect(),
                'adminNotifications' => collect(),
            ]);
        }

        if ($user->isAdmin()) {
            \App\Models\User::where('status', 'Pending')
                ->latest()
                ->get()
                ->each(function ($pendingUser) use ($user) {
                    AppNotification::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'type' => 'admin_signup',
                            'source_type' => 'user',
                            'source_id' => $pendingUser->id,
                        ],
                        [
                            'title' => 'New sign-up pending',
                            'body' => "{$pendingUser->name} submitted an account verification request.",
                            'action_url' => route('admin.view-user', $pendingUser),
                            'created_at' => $pendingUser->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }

        if ($user->isTeacher()) {
            $user->studentRequests()
                ->pending()
                ->with(['student.ownedProjects' => fn ($query) => $query->latest()])
                ->get()
                ->each(function ($requestNotification) use ($user) {
                    $student = $requestNotification->student;
                    $project = $student?->ownedProjects?->first();

                    AppNotification::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'type' => 'student_request',
                            'source_type' => 'adviser_student',
                            'source_id' => $requestNotification->id,
                        ],
                        [
                            'title' => 'New adviser request',
                            'body' => ($student?->name ?? 'A student') . ' from ' . ($project?->title ?? 'Student group') . ' requested you as adviser.',
                            'action_url' => route('advisers.pending-requests'),
                            'created_at' => $requestNotification->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }

        if (! $user->isAdmin()) {
            DefenseSchedule::with(['project.members', 'project.owner', 'project.adviser'])
                ->where('start_time', '>=', now()->subDay())
                ->where(function ($query) use ($user) {
                    $query->where('student_id', $user->id)
                        ->orWhere('adviser_id', $user->id)
                        ->orWhere('created_by', $user->id)
                        ->orWhereHas('project.members', fn ($members) => $members->where('users.id', $user->id))
                        ->orWhereHas('project', fn ($project) => $project->where('owner_id', $user->id)->orWhere('adviser_id', $user->id));
                })
                ->latest('start_time')
                ->limit(20)
                ->get()
                ->each(function ($schedule) use ($user) {
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
                });
        }

        $notifications = AppNotification::where('user_id', $user->id)
            ->latest()
            ->get()
            ->groupBy('type');

        $chatNotifications = $notifications->get('chat_mention', collect());
        $announcementNotifications = $user->isAdmin()
            ? collect()
            : $notifications->get('announcement', collect());
        $studentRequestNotifications = $user->isTeacher()
            ? $notifications->get('student_request', collect())
            : collect();
        $meetingNotifications = $user->isAdmin()
            ? collect()
            : $notifications->get('meeting_schedule', collect());
        $adminNotifications = $user->isAdmin()
            ? $notifications->get('admin_signup', collect())
            : collect();

        return view('notifications.index', compact('chatNotifications', 'announcementNotifications', 'studentRequestNotifications', 'meetingNotifications', 'adminNotifications'));
    })->name('notifications.index');
    Route::patch('/notifications/{notification}/read', function (AppNotification $notification) {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->markRead();
        Cache::forget('navigation-counts:' . Auth::id());

        return back();
    })->name('notifications.read');
    Route::get('/notifications/{notification}/open', function (AppNotification $notification) {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->markRead();
        Cache::forget('navigation-counts:' . Auth::id());

        return redirect($notification->action_url ?: route('notifications.index'));
    })->name('notifications.open');
    Route::patch('/notifications/{notification}/unread', function (AppNotification $notification) {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->markUnread();
        Cache::forget('navigation-counts:' . Auth::id());

        return back();
    })->name('notifications.unread');
    Route::patch('/notifications/sections/{type}/read', function (string $type) {
        $allowedTypes = ['chat_mention', 'announcement', 'student_request', 'meeting_schedule', 'admin_signup'];
        abort_unless(in_array($type, $allowedTypes, true), 404);

        AppNotification::where('user_id', Auth::id())
            ->where('type', $type)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        Cache::forget('navigation-counts:' . Auth::id());

        return back();
    })->name('notifications.sections.read');
    Route::get('/announcements/manage', function () {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.announcements');
        }

        if (! $user->isAdmin() && ! $user->isTeacher() && ! $user->canLeadGroup()) {
            abort(403, 'Access denied.');
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('announcements', 'audience_type')) {
            return redirect()->back()->with('error', 'Please run php artisan migrate before managing announcements.');
        }

        $announcements = \App\Models\Announcement::with('author')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get();

        $audienceDescription = match (true) {
            $user->isAdmin() => "Posts here appear on every user's dashboard.",
            $user->isTeacher() => 'Posts here appear for students you are currently handling.',
            $user->canLeadGroup() => 'Posts here appear for your group members only.',
            default => 'Create announcements for your audience.',
        };
        $postTitle = match (true) {
            $user->isTeacher() => 'Post an Announcement as an Adviser',
            $user->canLeadGroup() => 'Post an Announcement as a Leader',
            default => 'Post an Announcement',
        };

        $backRoute = match (true) {
            $user->isTeacher() => route('teacher.dashboard'),
            default => route('dashboard'),
        };

        $pageTitle = 'Announcements';

        return view('admin.announcements', compact('announcements', 'audienceDescription', 'backRoute', 'pageTitle', 'postTitle'));
    })->name('announcements.manage');
    Route::post('/announcements', function (Request $request) {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isTeacher() && ! $user->canLeadGroup()) {
            abort(403, 'Access denied.');
        }

        if (! Schema::hasTable('announcements')) {
            return back()->with('error', 'Please run php artisan migrate before posting announcements.');
        }

        if (! Schema::hasColumn('announcements', 'audience_type')) {
            return back()->with('error', 'Please run php artisan migrate before posting announcements.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $audienceType = 'global';
        $projectId = null;

        if ($user->isTeacher()) {
            $audienceType = 'adviser_students';
        } elseif ($user->canLeadGroup()) {
            $project = $user->ownedProjects()->latest()->first();

            if (! $project) {
                return back()->with('error', 'Create a group before posting announcements.');
            }

            $audienceType = 'project';
            $projectId = $project->id;
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            try {
                $attachmentPath = $request->file('attachment')->store('announcements', 'public');
                $attachmentName = $request->file('attachment')->getClientOriginalName();
            } catch (\Throwable $exception) {
                Log::error('Failed to store announcement attachment.', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                return back()->withInput()->with('error', 'The announcement attachment could not be uploaded. Try posting without an attachment first.');
            }
        }

        $announcementData = [
            'user_id' => $user->id,
            'message' => $validated['message'],
        ];

        if (Schema::hasColumn('announcements', 'audience_type')) {
            $announcementData['audience_type'] = $audienceType;
        }

        if (Schema::hasColumn('announcements', 'project_id')) {
            $announcementData['project_id'] = $projectId;
        }

        if (Schema::hasColumn('announcements', 'attachment_path')) {
            $announcementData['attachment_path'] = $attachmentPath;
        }

        if (Schema::hasColumn('announcements', 'attachment_name')) {
            $announcementData['attachment_name'] = $attachmentName;
        }

        try {
            $announcement = \App\Models\Announcement::create($announcementData);
        } catch (\Throwable $exception) {
            Log::error('Failed to create announcement.', [
                'user_id' => $user->id,
                'audience_type' => $audienceType,
                'project_id' => $projectId,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The announcement could not be posted. Please run migrations and check Railway logs.');
        }

        if (Schema::hasTable('app_notifications')) {
            $announcement->loadMissing(['author', 'project.owner', 'project.members']);

            $recipients = match ($audienceType) {
                'global' => \App\Models\User::whereNotNull('email')->get(),
                'adviser_students' => \App\Models\AdviserStudent::query()
                    ->approved()
                    ->active()
                    ->where('adviser_id', $user->id)
                    ->with(['student.ownedProjects.members'])
                    ->get()
                    ->pluck('student')
                    ->filter()
                    ->flatMap(fn ($leader) => collect([$leader])->merge($leader->ownedProjects->flatMap->members)),
                'project' => collect([$announcement->project?->owner])
                    ->filter()
                    ->merge($announcement->project?->members ?? collect()),
                default => collect(),
            };

            $recipients
                ->filter()
                ->unique('id')
                ->reject(fn ($recipient) => $recipient->id === $user->id || $recipient->isAdmin())
                ->each(function ($recipient) use ($announcement, $user) {
                    AppNotification::firstOrCreate(
                        [
                            'user_id' => $recipient->id,
                            'type' => 'announcement',
                            'source_type' => 'announcement',
                            'source_id' => $announcement->id,
                        ],
                        [
                            'title' => 'New announcement',
                            'body' => ($user->name ?? 'PaperTrail') . ' posted an announcement.',
                            'action_url' => route('dashboard'),
                            'created_at' => $announcement->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }

        $sentCount = 0;
        try {
            $sentCount = app(EmailNotificationService::class)->sendAnnouncementPosted($announcement);

            Log::info('Announcement email notification finished.', [
                'announcement_id' => $announcement->id,
                'sent_count' => $sentCount,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send announcement email notifications.', [
                'announcement_id' => $announcement->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $message = $sentCount > 0
            ? 'Announcement posted. Email notifications were sent.'
            : 'Announcement posted, but no email notifications were sent. Please check member emails and mail settings.';

        return back()->with($sentCount > 0 ? 'success' : 'warning', $message);
    })->name('announcements.store');
    Route::patch('/announcements/{announcement}', function (Request $request, \App\Models\Announcement $announcement) {
        $user = Auth::user();

        if (! $announcement->isManageableBy($user)) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $data = ['message' => $validated['message']];
        if ($request->hasFile('attachment')) {
            if ($announcement->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->attachment_path);
            }

            $data['attachment_path'] = $request->file('attachment')->store('announcements', 'public');
            $data['attachment_name'] = $request->file('attachment')->getClientOriginalName();
        }

        $announcement->update($data);

        return back()->with('success', 'Announcement updated successfully.');
    })->name('announcements.update');
    Route::delete('/announcements/{announcement}', function (\App\Models\Announcement $announcement) {
        if (! $announcement->isManageableBy(Auth::user())) {
            abort(403, 'Access denied.');
        }

        if ($announcement->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    })->name('announcements.destroy');
    Route::get('/announcements/{announcement}/attachment', function (\App\Models\Announcement $announcement) {
        if (! \App\Models\Announcement::visibleTo(Auth::user())->whereKey($announcement->id)->exists()) {
            abort(403, 'Access denied.');
        }

        if (! $announcement->attachment_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($announcement->attachment_path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($announcement->attachment_path, $announcement->attachment_name);
    })->name('announcements.attachment');
    
    // Adviser routes
    Route::get('/advisers/title-submission', [AdviserController::class, 'TitleSubmission'])->name('advisers.title-submission');
    Route::post('/advisers/send-request', [AdviserController::class, 'sendRequest'])->name('advisers.send-request');
    Route::delete('/advisers/requests/{adviserStudent}', [AdviserController::class, 'removeRequest'])->name('advisers.requests.remove');
    Route::get('/advisers/pending-requests', [AdviserController::class, 'pendingRequests'])->name('advisers.pending-requests');
    Route::post('/advisers/respond/{adviserStudent}', [AdviserController::class, 'respondToRequest'])->name('advisers.respond');
    Route::patch('/advisers/{adviserStudent}/archive', [AdviserController::class, 'archiveStudentGroup'])->name('advisers.archive');
    Route::delete('/advisers/{adviserStudent}', [AdviserController::class, 'releaseAdviser'])->name('advisers.release');
    Route::get('/suggested-ai', fn () => redirect()->route('advisers.title-submission'));
    Route::post('/suggested-ai', [SuggestedAIController::class, 'index'])->name('suggested-ai');
    Route::post('/title-submission',[TitleSubmissionController::class, 'store'])->name('title-submission.store');
    Route::get('/my-advisers', [AdviserController::class, 'myAdvisers'])->name('advisers.my-advisers');
    Route::get('/my-students', [AdviserController::class, 'myStudents'])->name('advisers.my-students');
    Route::get('/advisers/progress-tracker', function () use ($manuscriptStages) {
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Access denied. Teachers only.');
        }

        $stages = $manuscriptStages();
        $stageWeight = 100 / $stages->count();

        $advisees = \App\Models\Project::query()
            ->where('adviser_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->with(['owner', 'tasks'])
            ->latest('updated_at')
            ->get()
            ->map(function ($project) use ($stages, $stageWeight) {
                $chapters = $stages->map(function ($stageName, $chapter) use ($project, $stageWeight) {
                    $tasks = $project->tasks->where('chapter', $chapter);
                    $totalTasks = $tasks->count();
                    $completedTasks = $tasks->where('is_completed', true)->count();
                    $contribution = $totalTasks > 0
                        ? round(($completedTasks / $totalTasks) * $stageWeight, 2)
                        : 0;

                    return (object) [
                        'number' => $chapter,
                        'name' => $stageName,
                        'totalTasks' => $totalTasks,
                        'completedTasks' => $completedTasks,
                        'contribution' => $contribution,
                        'status' => "{$completedTasks}/{$totalTasks} tasks completed",
                    ];
                });

                return (object) [
                    'projectId' => $project->id,
                    'groupName' => $project->title,
                    'groupCourse' => $project->group_course ?? $project->owner?->course ?? 'Unassigned Course',
                    'ownerName' => $project->owner?->name ?? 'Student Group',
                    'progress' => round($chapters->sum('contribution'), 2),
                    'chapters' => $chapters,
                ];
            });
        $courseGroups = $advisees->groupBy('groupCourse');

        return view('advisers.progress-tracker', compact('advisees', 'courseGroups'));
    })->name('advisers.progress-tracker');
    Route::get('/advisers/todo/{chapterName?}', function (Request $request, ?string $chapterName = null) use ($manuscriptStages) {
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Access denied. Teachers only.');
        }

        $manuscriptStages = $manuscriptStages();
        $projects = \App\Models\Project::where('adviser_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->orderBy('title')
            ->get();
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;
        $selectedProject = $projects->firstWhere('id', $selectedProjectId);
        $projectIds = $projects->pluck('id');
        $todos = \App\Models\ProjectTask::query()
            ->where('adviser_id', Auth::id())
            ->whereIn('project_id', $projectIds)
            ->when($selectedProject, fn ($query) => $query->where('project_id', $selectedProject->id))
            ->orderBy('chapter')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('chapter');
        $canManageTasks = true;
        $canToggleTasks = false;
        $courses = ['Information Technology', 'Information Systems', 'Computer Science'];
        $selectedChapter = null;

        return view('teacher.todo-list', compact('todos', 'chapterName', 'projects', 'selectedProject', 'canManageTasks', 'canToggleTasks', 'courses', 'selectedChapter', 'manuscriptStages'));
    })->name('advisers.todo');
    Route::get('/teacher/todo-list', function (Request $request) use ($manuscriptStages) {
        $user = Auth::user();

        if (! $user->isTeacher() && ! $user->isStudentGroupRole()) {
            abort(403, 'Access denied.');
        }

        $manuscriptStages = $manuscriptStages();
        if ($user->isTeacher()) {
            $projects = \App\Models\Project::where('adviser_id', $user->id)
                ->where('status', '!=', 'archived')
                ->orderBy('title')
                ->get();
        } elseif ($user->canLeadGroup()) {
            $projects = $user->ownedProjects()
                ->with(['owner', 'members'])
                ->whereNotNull('adviser_id')
                ->where('status', '!=', 'archived')
                ->orderBy('title')
                ->get();
        } else {
            $projects = $user->joinedProjects()
                ->with(['owner', 'members'])
                ->whereNotNull('adviser_id')
                ->where('status', '!=', 'archived')
                ->orderBy('title')
                ->get();
        }
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;
        $selectedProject = $projects->firstWhere('id', $selectedProjectId);
        $projectIds = $projects->pluck('id');
        $todos = \App\Models\ProjectTask::query()
            ->whereIn('project_id', $projectIds)
            ->when($user->isTeacher(), fn ($query) => $query->where('adviser_id', $user->id))
            ->when($user->canLeadGroup(), fn ($query) => $query->whereHas('project', fn ($project) => $project->where('owner_id', $user->id)))
            ->when($user->isStudent() && ! $user->canLeadGroup(), fn ($query) => $query->whereHas('project.members', fn ($members) => $members->where('users.id', $user->id)))
            ->when($selectedProject, fn ($query) => $query->where('project_id', $selectedProject->id))
            ->orderBy('chapter')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('chapter');
        $selectedChapter = null;
        if ($user->isStudentGroupRole()) {
            $requestedChapter = $request->integer('chapter');
            $selectedChapter = $manuscriptStages->has($requestedChapter)
                ? $requestedChapter
                : (int) ($todos->keys()->first() ?? 0);
            $todos = $todos->filter(fn ($tasks, $chapter) => (int) $chapter === $selectedChapter);
        }
        $chapterName = null;
        $canManageTasks = $user->isTeacher();
        $canToggleTasks = $user->canLeadGroup();
        $canFilterTasks = $user->isStudentGroupRole();
        $courses = ['Information Technology', 'Information Systems', 'Computer Science'];
        $completionUsers = collect();

        if ($canToggleTasks && $selectedProject) {
            $completionUsers = collect([$selectedProject->owner])
                ->filter()
                ->merge($selectedProject->members->reject(fn ($member) => $member->isLeader()))
                ->unique('id')
                ->values();
        }

        return view('teacher.todo-list', compact('todos', 'chapterName', 'projects', 'selectedProject', 'canManageTasks', 'canToggleTasks', 'canFilterTasks', 'courses', 'selectedChapter', 'completionUsers', 'manuscriptStages'));
    })->name('todo.index');
    Route::post('/teacher/todo-list', function (Request $request) use ($manuscriptStages) {
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Access denied. Teachers only.');
        }

        $validated = $request->validate([
            'assignment_scope' => 'required|in:project,course',
            'project_id' => 'required_if:assignment_scope,project|nullable|exists:projects,id',
            'course' => 'required_if:assignment_scope,course|nullable|in:Information Technology,Information Systems,Computer Science',
            'chapter' => ['required', 'integer', \Illuminate\Validation\Rule::in($manuscriptStages()->keys()->all())],
            'tasks' => 'required|array|min:1',
            'tasks.*' => 'required|string|max:255',
        ]);

        $projects = \App\Models\Project::where('adviser_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->when($validated['assignment_scope'] === 'project', fn ($query) => $query->where('id', $validated['project_id']))
            ->when($validated['assignment_scope'] === 'course', function ($query) use ($validated) {
                $query->where(function ($courseQuery) use ($validated) {
                    $courseQuery->where('group_course', $validated['course'])
                        ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('course', $validated['course']));
                });
            })
            ->get();

        if ($projects->isEmpty()) {
            return back()->with('error', 'No advised groups matched that selection.');
        }

        foreach ($validated['tasks'] as $taskTitle) {
            $courseTaskGroupId = $validated['assignment_scope'] === 'course'
                ? \Illuminate\Support\Str::uuid()->toString()
                : null;

            foreach ($projects as $project) {
                \App\Models\ProjectTask::create([
                    'project_id' => $project->id,
                    'adviser_id' => Auth::id(),
                    'assignment_course' => $validated['assignment_scope'] === 'course' ? $validated['course'] : null,
                    'course_task_group_id' => $courseTaskGroupId,
                    'chapter' => $validated['chapter'],
                    'title' => $taskTitle,
                ]);
            }
        }

        foreach ($projects as $project) {
            app(EmailNotificationService::class)->sendTaskAssigned($project, Auth::user(), (int) $validated['chapter'], $validated['tasks']);
        }

        return back()->with('success', 'To-do list assigned successfully.');
    })->name('todo.store');
    Route::patch('/teacher/tasks/{task}', function (Request $request, \App\Models\ProjectTask $task) {
        if (!Auth::user()->isTeacher() || $task->adviser_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }
        $task->loadMissing('project.owner');
        if (! $task->project || $task->project->adviser_id !== Auth::id() || $task->project->status === 'archived') {
            abort(403, 'Archived group tasks cannot be changed.');
        }

        $validated = $request->validate([
            'chapter' => 'required|integer|min:0|max:5',
            'title' => 'required|string|max:255',
        ]);

        if ($task->course_task_group_id) {
            \App\Models\ProjectTask::where('adviser_id', Auth::id())
                ->where('course_task_group_id', $task->course_task_group_id)
                ->whereHas('project', fn ($project) => $project->where('adviser_id', Auth::id())->where('status', '!=', 'archived'))
                ->update($validated);
        } elseif ($taskCourse = ($task->assignment_course ?? $task->project?->group_course ?? $task->project?->owner?->course)) {
            \App\Models\ProjectTask::where('adviser_id', Auth::id())
                ->where('chapter', $task->chapter)
                ->where('title', $task->title)
                ->whereHas('project', function ($query) use ($taskCourse) {
                    $query->where('group_course', $taskCourse)
                        ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('course', $taskCourse));
                })
                ->whereHas('project', fn ($project) => $project->where('adviser_id', Auth::id())->where('status', '!=', 'archived'))
                ->update($validated);
        } else {
            $task->update($validated);
        }

        return back()->with('success', 'Task updated successfully.');
    })->name('todo.update');
    Route::delete('/teacher/tasks/{task}', function (\App\Models\ProjectTask $task) {
        if (!Auth::user()->isTeacher() || $task->adviser_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }
        $task->loadMissing('project.owner');
        if (! $task->project || $task->project->adviser_id !== Auth::id() || $task->project->status === 'archived') {
            abort(403, 'Archived group tasks cannot be deleted.');
        }

        if ($task->course_task_group_id) {
            \App\Models\ProjectTask::where('adviser_id', Auth::id())
                ->where('course_task_group_id', $task->course_task_group_id)
                ->whereHas('project', fn ($project) => $project->where('adviser_id', Auth::id())->where('status', '!=', 'archived'))
                ->delete();
        } elseif ($taskCourse = ($task->assignment_course ?? $task->project?->group_course ?? $task->project?->owner?->course)) {
            \App\Models\ProjectTask::where('adviser_id', Auth::id())
                ->where('chapter', $task->chapter)
                ->where('title', $task->title)
                ->whereHas('project', function ($query) use ($taskCourse) {
                    $query->where('group_course', $taskCourse)
                        ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('course', $taskCourse));
                })
                ->whereHas('project', fn ($project) => $project->where('adviser_id', Auth::id())->where('status', '!=', 'archived'))
                ->delete();
        } else {
            $task->delete();
        }

        return back()->with('success', 'Task deleted successfully.');
    })->name('todo.destroy');
    Route::patch('/teacher/tasks/{task}/toggle', function (Request $request, \App\Models\ProjectTask $task) {
        $task->load('project.owner', 'project.members');

        if (!Auth::user()->canLeadGroup() || $task->project?->owner_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }
        if ($task->project->status === 'archived' || ! $task->project->adviser_id) {
            abort(403, 'Archived group tasks cannot be changed.');
        }

        $completionUsers = collect([$task->project->owner])
            ->filter()
            ->merge($task->project->members->reject(fn ($member) => $member->isLeader()))
            ->unique('id')
            ->values();

        $validated = $request->validate([
            'is_completed' => 'nullable|boolean',
            'completion_user_id' => [
                $request->boolean('is_completed') ? 'required' : 'nullable',
                'integer',
                \Illuminate\Validation\Rule::in($completionUsers->pluck('id')->all()),
            ],
        ]);
        $isCompleted = $request->boolean('is_completed');
        $completedBy = $isCompleted
            ? $completionUsers->firstWhere('id', (int) $validated['completion_user_id'])
            : null;

        $task->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
            'completion_note' => $completedBy?->name,
        ]);

        return back();
    })->name('todo.toggle');

    // Leader group management
    Route::get('/group-description', [GroupController::class, 'show'])->name('group-description.show');
    Route::get('/group-description/{project}', [GroupController::class, 'details'])->name('group-description.details');
    Route::patch('/group-description', [GroupController::class, 'update'])->name('group-description.update');
    Route::post('/group-description/share-link', [GroupController::class, 'shareLink'])->name('group-description.share-link');
    Route::delete('/group-description/members/{member}', [GroupController::class, 'removeMember'])->name('group-description.members.remove');
    
    // Admin routes
    Route::get('/admin/announcements', function () {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Admins only.');
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('announcements', 'audience_type')) {
            return redirect()->route('admin.dashboard')->with('error', 'Please run php artisan migrate before managing announcements.');
        }

        $announcements = \App\Models\Announcement::with('author')
            ->where('audience_type', 'global')
            ->latest()
            ->get();
        $audienceDescription = "Admin announcements appear on every user's dashboard.";
        $backRoute = route('admin.dashboard');
        $pageTitle = 'Admin Announcements';
        $postTitle = 'Post an Announcement as an Admin';

        return view('admin.announcements', compact('announcements', 'audienceDescription', 'backRoute', 'pageTitle', 'postTitle'));
    })->name('admin.announcements');
    Route::get('/admin/pending-users', [AdminController::class, 'pendingUsers'])->name('admin.pending-users');
    Route::get('/admin/users/{user}', [AdminController::class, 'viewUser'])->name('admin.view-user');
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('admin.verify-user');
    Route::post('/admin/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('admin.reject-user');
    Route::get('/admin/users/{user}/document', [AdminController::class, 'viewDocument'])->name('admin.view-document');
    Route::get('/admin/all-users', [AdminController::class, 'allUsers'])->name('admin.all-users');
    Route::post('/admin/users/{user}/update-role', [AdminController::class, 'updateUserRole'])->name('admin.update-user-role');
    Route::post('/admin/users/{user}/update-status', [AdminController::class, 'updateUserStatus'])->name('admin.update-user-status');
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.delete-user');

    // Project routes
    Route::get('/projects/archived', [ProjectController::class, 'archived'])->name('projects.archived');
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/invitations', [ProjectController::class, 'generateInvitation'])->name('projects.invitations.generate');
    Route::get('/project-invitations/{token}', [ProjectController::class, 'showInvitation'])->name('projects.accept-invitation');
    Route::post('/project-invitations/{token}/accept', [ProjectController::class, 'acceptInvitation'])->name('projects.accept-invitation.store');
    Route::post('/project-invitations/{token}/decline', [ProjectController::class, 'declineInvitation'])->name('projects.decline-invitation');
    Route::post('/projects/{project}/folders', [ProjectController::class, 'createFolder'])->name('projects.create-folder');
    Route::post('/projects/{project}/upload', [ProjectController::class, 'uploadDocuments'])->name('projects.upload-documents');
    Route::get('/projects/{project}/documents/{document}/preview', [ProjectController::class, 'previewDocument'])->name('projects.preview-document');
    Route::get('/projects/{project}/documents/{document}/download', [ProjectController::class, 'downloadDocument'])->name('projects.download-document');
    Route::delete('/projects/{project}/documents/{document}', [ProjectController::class, 'deleteDocument'])->name('projects.delete-document');
    Route::delete('/projects/{project}/folders/{folder}', [ProjectController::class, 'deleteFolder'])->name('projects.delete-folder');
    
    // Defense Schedule routes
    Route::resource('defense-schedule', DefenseScheduleController::class);
    Route::get('/defense-schedule-events', [DefenseScheduleController::class, 'getEvents'])->name('defense-schedule.events');
    Route::get('/students/{student}/projects', [DefenseScheduleController::class, 'getStudentProjects'])->name('students.projects');
    
    // Chat routes
    Route::prefix('chat')->name('chat.')->middleware('auth')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/archived', [ChatController::class, 'archived'])->name('archived');
        Route::get('/unread-summary', [ChatController::class, 'unreadSummary'])->name('unread-summary');
        Route::get('/files/{message}', [ChatController::class, 'showFile'])->name('files.show');
        Route::post('/rooms', [ChatController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{chatRoom}', [ChatController::class, 'show'])->name('rooms.show');
        Route::get('/rooms/{chatRoom}/messages', [ChatController::class, 'getMessages'])->name('rooms.messages');
        Route::post('/rooms/{chatRoom}/messages', [ChatController::class, 'sendMessage'])->name('rooms.send-message');
        Route::post('/projects/{project}/chat', [ChatController::class, 'createProjectChat'])->name('project.create');
        
        // Enhanced chat features
        Route::post('/rooms/{chatRoom}/participants', [ChatController::class, 'addParticipants'])->name('rooms.add-participants');
        Route::delete('/rooms/{chatRoom}/participants', [ChatController::class, 'removeParticipant'])->name('rooms.remove-participant');
        Route::post('/rooms/{chatRoom}/participant-role', [ChatController::class, 'updateParticipantRole'])->name('rooms.update-participant-role');
        Route::get('/rooms/{chatRoom}/available-users', [ChatController::class, 'getAvailableUsers'])->name('rooms.available-users');
        Route::patch('/rooms/{chatRoom}/messages/{message}', [ChatController::class, 'editMessage'])->name('rooms.edit-message');
        Route::delete('/rooms/{chatRoom}/messages/{message}', [ChatController::class, 'deleteMessage'])->name('rooms.delete-message');
        Route::post('/rooms/{chatRoom}/messages/{message}/pin', [ChatController::class, 'togglePin'])->name('rooms.messages.toggle-pin');
        Route::post('/rooms/{chatRoom}/pin', [ChatController::class, 'toggleRoomPin'])->name('rooms.toggle-pin');
        Route::post('/rooms/{chatRoom}/messages/seen', [ChatController::class, 'markAsSeen'])->name('rooms.mark-seen');
        
        // New features
        Route::post('/rooms/{chatRoom}/leave', [ChatController::class, 'leaveChatRoom'])->name('rooms.leave');
        Route::delete('/rooms/{chatRoom}', [ChatController::class, 'deleteChatRoom'])->name('rooms.delete');
        Route::post('/rooms/{chatRoom}/typing', [ChatController::class, 'updateTypingStatus'])->name('rooms.typing');
        Route::get('/rooms/{chatRoom}/typing', [ChatController::class, 'getTypingUsers'])->name('rooms.get-typing');
        
        // Emoji reactions
        Route::post('/rooms/{chatRoom}/messages/{message}/reactions', [ChatController::class, 'toggleReaction'])->name('rooms.messages.toggle-reaction');
        Route::get('/rooms/{chatRoom}/messages/{message}/reactions', [ChatController::class, 'getMessageReactions'])->name('rooms.messages.get-reactions');
    });
    
    // Test route for chat system
    Route::get('/chat-test', function () {
        return response()->json([
            'status' => 'Chat system is working!',
            'controller_exists' => class_exists('App\Http\Controllers\ChatController'),
            'service_exists' => class_exists('App\Services\GoogleChatService'),
            'chat_route' => route('chat.index'),
            'auth_user' => auth()->check() ? auth()->user()->id : 'Not authenticated',
            'users_count' => \App\Models\User::count(),
            'chat_rooms_count' => \App\Models\ChatRoom::count()
        ]);
    })->name('chat.test');
    
    // Test route for creating a simple chat room
    Route::post('/chat-test-create', function () {
        try {
            $service = new \App\Services\GoogleChatService();
            $users = \App\Models\User::limit(2)->get();
            
            if ($users->count() < 1) {
                return response()->json(['error' => 'No users found for testing']);
            }
            
            $chatRoom = $service->createChatRoom(
                'Test Chat Room',
                'This is a test chat room',
                $users->toArray()
            );
            
            return response()->json([
                'success' => $chatRoom ? true : false,
                'chat_room_id' => $chatRoom ? $chatRoom->id : null,
                'participants_count' => $chatRoom ? $chatRoom->participants()->count() : 0
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('chat.test.create');
    
    // Debug route for specific chat room
    Route::get('/chat-debug/{id}', function ($id) {
        try {
            $chatRoom = \App\Models\ChatRoom::find($id);
            if (!$chatRoom) {
                return response()->json(['error' => 'Chat room not found', 'id' => $id]);
            }
            
            $user = auth()->user();
            return response()->json([
                'chat_room' => [
                    'id' => $chatRoom->id,
                    'name' => $chatRoom->name,
                    'participants_count' => $chatRoom->participants()->count(),
                    'messages_count' => $chatRoom->messages()->count(),
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'is_participant' => $chatRoom->hasParticipant($user),
                ],
                'participants' => $chatRoom->participants()->get(['id', 'firstname', 'lastname'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('chat.debug');
    
    // Test messages route
    Route::get('/test-messages/{id}', function ($id) {
        try {
            $chatRoom = \App\Models\ChatRoom::findOrFail($id);
            $user = auth()->user();
            
            // Test the pivot update
            $chatRoom->participants()
                     ->wherePivot('user_id', $user->id)
                     ->updateExistingPivot($user->id, ['last_read_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pivot update successful',
                'chat_room' => $chatRoom->name,
                'user' => $user->firstname . ' ' . $user->lastname
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('test.messages');
    
    // Debug route for available users
    Route::get('/debug-users/{roomId}', function ($roomId) {
        try {
            $chatRoom = \App\Models\ChatRoom::findOrFail($roomId);
            $existingParticipantIds = $chatRoom->participants()->pluck('users.id')->toArray();
            $allUsers = \App\Models\User::where('status', 'Verified')->get(['id', 'firstname', 'lastname', 'email', 'role']);
            $availableUsers = \App\Models\User::whereNotIn('id', $existingParticipantIds)
                                             ->where('status', 'Verified')
                                             ->get(['id', 'firstname', 'lastname', 'email', 'role']);
            
            return response()->json([
                'chat_room_id' => $roomId,
                'existing_participant_ids' => $existingParticipantIds,
                'all_verified_users_count' => $allUsers->count(),
                'all_verified_users' => $allUsers->toArray(),
                'available_users_count' => $availableUsers->count(),
                'available_users' => $availableUsers->toArray()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('debug.users');
});

require __DIR__.'/auth.php';
