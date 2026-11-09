<?php

namespace App\Http\Controllers;

use App\Models\AdviserStudent;
use App\Models\AppNotification;
use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\TitleSubmission;
use App\Services\EmailNotificationService;

class AdviserController extends Controller
{
    /**
     * Show available advisers for students to select
     */
    public function index()
    {
        // Only group leaders can choose and request advisers
        if (!Auth::user()->canLeadGroup()) {
            abort(403, 'Access denied. Group leaders only.');
        }

        $advisers = User::where('role', 'Teacher')
            ->where('status', 'Verified')
            ->orderBy('lastname')
            ->get();

        $currentRequests = Auth::user()->adviserRequests()
            ->currentRequest()
            ->with('adviser')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('advisers.index', compact('advisers', 'currentRequests'));
    }

    /**
     * Send request to an adviser
     */
    public function sendRequest(Request $request)
    {
        if (!Auth::user()->canLeadGroup()) {
            abort(403, 'Access denied. Group leaders only.');
        }

        $leader = Auth::user();
        $mode = $request->input('mode') === 'single' ? 'single' : 'multiple';
        $batch = $request->has('match_token');
        $page = $mode === 'single' ? ['mode' => 'single'] : [];
        $redirect = redirect()->route($batch ? 'advisers.matches' : 'advisers.title-submission', $page);
        $validated = $request->validate([
            'match_token' => $batch ? 'required|uuid' : 'exclude',
            'adviser_id' => $batch ? 'exclude' : 'required|integer|exists:users,id',
            'adviser_ids' => $batch ? 'required|array|min:1|max:6' : 'exclude',
            'adviser_ids.*' => 'required|integer|distinct',
            'message' => 'nullable|string|max:500',
        ]);

        $ids = $batch ? $validated['adviser_ids'] : [$validated['adviser_id']];
        $match = $batch ? $request->session()->get("adviser_matches.{$mode}") : null;
        if ($batch && (!$match || $match['token'] !== $validated['match_token'] || array_diff($ids, array_keys($match['advisers'])))) {
            return $redirect->withErrors(['adviser_id' => 'Select advisers from your current match results.']);
        }

        $advisers = User::whereKey(array_map('intval', $ids))->where('role', 'Teacher')
            ->when($batch, fn ($query) => $query->where('status', 'Verified'))->get();
        if ($advisers->count() !== count($ids)) {
            return $redirect->withErrors(['adviser_id' => 'One or more selected users are no longer advisers.']);
        }

        $sent = DB::transaction(function () use ($leader, $advisers, $validated, $match) {
            // Sending, withdrawing, and accepting requests share the group leader's lock.
            User::whereKey($leader->id)->lockForUpdate()->firstOrFail();

            if ($leader->adviserRequests()->approved()->active()->exists()) {
                return 'Your group already has an adviser.';
            }

            $pendingIds = $leader->adviserRequests()->pending()->pluck('adviser_id');
            $newAdvisers = $advisers->whereNotIn('id', $pendingIds);
            if ($newAdvisers->isEmpty()) {
                return 'You already have pending requests with the selected advisers.';
            }

            return $newAdvisers->map(function (User $adviser) use ($leader, $validated, $match) {
                $adviserRequest = $leader->adviserRequests()->updateOrCreate(
                    ['adviser_id' => $adviser->id],
                    [
                        'message' => $validated['message'] ?? null,
                        'proposals' => $match['proposals'] ?? null,
                        'response_message' => null,
                        'requested_at' => now(),
                        'responded_at' => null,
                        'status' => 'pending',
                    ]
                );

                if (Schema::hasTable('app_notifications')) {
                    AppNotification::updateOrCreate(
                        [
                            'user_id' => $adviser->id,
                            'type' => 'student_request',
                            'source_type' => 'adviser_student',
                            'source_id' => $adviserRequest->id,
                        ],
                        [
                            'title' => 'New adviser request',
                            'body' => $leader->name . ' sent you an adviser request.',
                            'action_url' => route('advisers.pending-requests'),
                            'read_at' => null,
                        ]
                    );
                }

                return $adviserRequest;
            });
        }, 3);

        if (is_string($sent)) {
            return $redirect->withErrors(['adviser_id' => $sent]);
        }

        foreach ($sent as $adviserRequest) {
            Cache::forget("navigation-counts:{$adviserRequest->adviser_id}");
            app(EmailNotificationService::class)->sendAdviserRequestReceived($adviserRequest);
        }

        return $redirect->with('success', 'Requests sent to '.$sent->count().' adviser(s).');
    }

    /**
     * Show pending requests for teachers
     */
    public function pendingRequests()
    {
        // Only teachers can access this
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Access denied. Teachers only.');
        }

        $pendingRequests = Auth::user()->studentRequests()
            ->pending()
            ->with(['student.ownedProjects' => fn ($query) => $query->with(['owner', 'members'])->latest()])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('advisers.pending-requests', compact('pendingRequests'));
    }

    /**
     * Respond to a student request
     */
    public function respondToRequest(Request $request, AdviserStudent $adviserStudent)
    {
        // Check if the authenticated user is the adviser for this request
        if ($adviserStudent->adviser_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'response_message' => 'nullable|string|max:500'
        ]);

        $result = DB::transaction(function () use ($adviserStudent, $request) {
            $leader = User::whereKey($adviserStudent->student_id)->lockForUpdate()->firstOrFail();
            $adviserStudent = $leader->adviserRequests()->pending()->whereKey($adviserStudent->id)->lockForUpdate()->first();

            if (!$adviserStudent || $leader->adviserRequests()->approved()->active()->exists()) {
                return null;
            }

            $wasArchivedReactivation = $adviserStudent->isArchived();
            if ($request->status === 'rejected' && $wasArchivedReactivation) {
                $adviserStudent->update([
                    'status' => 'approved',
                    'response_message' => $request->response_message,
                    'responded_at' => now(),
                ]);

                return ['message' => 'Request rejected. The group remains in archived history.', 'adviserIds' => []];
            }

            $adviserStudent->update([
                'status' => $request->status,
                'response_message' => $request->response_message,
                'responded_at' => now(),
                'archived_at' => $request->status === 'approved' ? null : $adviserStudent->archived_at,
            ]);

            if ($request->status !== 'approved') {
                return ['message' => 'Request rejected successfully!', 'adviserIds' => []];
            }

            $otherRequests = $leader->adviserRequests()->pending()->lockForUpdate()->get();
            $this->deleteRequestNotifications($otherRequests->modelKeys());
            foreach ($otherRequests as $otherRequest) {
                $this->withdrawPendingRequest($otherRequest);
            }

            $projects = Project::where('owner_id', $adviserStudent->student_id)->get();

            $projects
                ->each
                ->update(['adviser_id' => $adviserStudent->adviser_id]);

            $projects
                ->each
                ->syncCourseTasksFromAdviser();

            $projectIds = $projects->pluck('id');

            ChatRoom::whereIn('project_id', $projectIds)
                ->where('type', 'project')
                ->get()
                ->each(fn (ChatRoom $room) => $room->addParticipant($adviserStudent->adviser, 'moderator'));

            if ($wasArchivedReactivation) {
                $this->markProjectChatRoomsRead($projectIds);
            }
            return [
                'message' => 'Request approved successfully!',
                'adviserIds' => $otherRequests->pluck('adviser_id')->all(),
            ];
        }, 3);

        if ($result === null) {
            return back()->with('error', 'This adviser request is no longer available.');
        }

        foreach ([Auth::id(), ...$result['adviserIds']] as $adviserId) {
            Cache::forget("navigation-counts:{$adviserId}");
        }

        return back()->with('success', $result['message']);
    }

    public function removeRequest(AdviserStudent $adviserStudent)
    {
        if ($adviserStudent->student_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }

        $adviserName = $adviserStudent->adviser?->name ?? 'The adviser';

        $removed = DB::transaction(function () use ($adviserStudent) {
            User::whereKey($adviserStudent->student_id)->lockForUpdate()->firstOrFail();
            $relationship = AdviserStudent::whereKey($adviserStudent->id)->lockForUpdate()->firstOrFail();
            if ($relationship->status === 'approved') {
                return false;
            }

            $this->deleteRequestNotifications([$relationship->id]);
            $this->withdrawPendingRequest($relationship);

            return true;
        }, 3);

        if (!$removed) {
            return redirect()
                ->route('advisers.title-submission')
                ->with('error', "Accepted adviser requests can't be deleted from Find Advisers. Use Remove Adviser or Stop Advising to end the adviser assignment.");
        }

        Cache::forget("navigation-counts:{$adviserStudent->adviser_id}");

        return redirect()
            ->route('advisers.title-submission', ['tab' => 'pending', 'mode' => request('mode')])
            ->with('success', "Request to {$adviserName} removed successfully.");
    }

    private function deleteRequestNotifications(array $requestIds): void
    {
        if ($requestIds !== [] && Schema::hasTable('app_notifications')) {
            AppNotification::where('type', 'student_request')
                ->where('source_type', 'adviser_student')
                ->whereIn('source_id', $requestIds)
                ->delete();
        }
    }

    private function withdrawPendingRequest(AdviserStudent $relationship): void
    {
        if ($relationship->isArchived()) {
            $relationship->update([
                'status' => 'approved',
                'responded_at' => $relationship->responded_at ?? $relationship->archived_at,
            ]);
        } else {
            $relationship->delete();
        }
    }

    /**
     * End an approved adviser relationship from either side.
     */
    public function releaseAdviser(AdviserStudent $adviserStudent)
    {
        $user = Auth::user();

        if ($adviserStudent->student_id !== $user->id && $adviserStudent->adviser_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        $projectIds = $this->affectedProjectIds($adviserStudent);

        $this->deleteReleasedAdviserTasks($projectIds, $adviserStudent->adviser_id);

        Project::whereIn('id', $projectIds)->update(['adviser_id' => null]);

        ChatRoom::whereIn('project_id', $projectIds)
            ->where('type', 'project')
            ->get()
            ->each(fn (ChatRoom $room) => $room->participants()->detach($adviserStudent->adviser_id));

        $adviserName = $adviserStudent->adviser?->name ?? 'The adviser';
        $adviserStudent->delete();

        return back()->with('success', "{$adviserName} is no longer assigned as adviser.");
    }

    private function affectedProjectIds(AdviserStudent $adviserStudent)
    {
        return Project::where('owner_id', $adviserStudent->student_id)
            ->where(function ($query) use ($adviserStudent) {
                $query->where('adviser_id', $adviserStudent->adviser_id)
                    ->orWhereHas('tasks', fn ($tasks) => $tasks->where('adviser_id', $adviserStudent->adviser_id));
            })
            ->pluck('id');
    }

    private function deleteReleasedAdviserTasks($projectIds, int $adviserId): void
    {
        if ($projectIds->isEmpty()) {
            return;
        }

        ProjectTask::whereIn('project_id', $projectIds)
            ->where('adviser_id', $adviserId)
            ->delete();
    }

    private function markProjectChatRoomsRead($projectIds): void
    {
        $chatRoomIds = ChatRoom::whereIn('project_id', $projectIds)
            ->where('type', 'project')
            ->pluck('id');

        if ($chatRoomIds->isEmpty()) {
            return;
        }

        DB::table('chat_participants')
            ->whereIn('chat_room_id', $chatRoomIds)
            ->update([
                'last_read_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('chat_participants')
            ->whereIn('chat_room_id', $chatRoomIds)
            ->pluck('user_id')
            ->unique()
            ->each(fn ($userId) => Cache::forget("navigation-counts:{$userId}"));
    }

    /**
     * Archive a completed group from the adviser's active list.
     */
    public function archiveStudentGroup(AdviserStudent $adviserStudent)
    {
        if ($adviserStudent->adviser_id !== Auth::id()) {
            abort(403, 'Only the assigned adviser can archive this group.');
        }

        if (!Schema::hasColumn('adviser_student', 'archived_at')) {
            return back()->with('error', 'Run the latest migrations before archiving groups.');
        }

        if ($adviserStudent->status !== 'approved') {
            return back()->with('error', 'Only approved adviser groups can be archived.');
        }

        DB::transaction(function () use ($adviserStudent) {
            $archivedAt = now();
            $projectIds = $this->affectedProjectIds($adviserStudent);

            $adviserStudent->update(['archived_at' => $archivedAt]);

            Project::whereIn('id', $projectIds)
                ->where('adviser_id', $adviserStudent->adviser_id)
                ->update(['adviser_id' => null]);

            ChatRoom::whereIn('project_id', $projectIds)
                ->where('type', 'project')
                ->with('participants')
                ->get()
                ->each(function (ChatRoom $room) use ($adviserStudent) {
                    if ($adviserStudent->adviser && ! $room->hasParticipant($adviserStudent->adviser)) {
                        $room->addParticipant($adviserStudent->adviser, 'moderator');
                    }

                    $room->messages()->create([
                        'user_id' => $adviserStudent->adviser_id,
                        'message' => 'This adviser chat has been archived. The conversation is now read-only.',
                        'message_type' => 'system',
                    ]);
                });
        });

        return back()->with('success', 'Group archived. Chat history is saved as read-only.');
    }

    /**
     * Show my advisers (for students)
     */
    public function myAdvisers()
    {
        if (!Auth::user()->canLeadGroup()) {
            abort(403, 'Access denied. Group leaders only.');
        }

        $advisers = Auth::user()
            ->adviserRequests()
            ->approved()
            ->with([
                'adviser',
                'student.ownedProjects' => fn ($query) => $query->with('chatRooms')->latest(),
            ])
            ->latest('responded_at')
            ->get();
        
        return view('advisers.my-advisers', compact('advisers'));
    }

    /**
     * Show my students (for teachers)
     */
    public function myStudents()
    {
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Access denied. Teachers only.');
        }

        $studentsQuery = Auth::user()->students()
            ->with(['student.ownedProjects' => fn ($query) => $query->latest()]);

        if (Schema::hasColumn('adviser_student', 'archived_at')) {
            $studentsQuery->active();
        }

        $students = $studentsQuery->get();
        $archivedStudents = Auth::user()
            ->studentRequests()
            ->approved()
            ->archived()
            ->with(['student.ownedProjects' => fn ($query) => $query->with('chatRooms')->latest()])
            ->latest('archived_at')
            ->get();
        
        return view('advisers.my-students', compact('students', 'archivedStudents'));
    }

        /**
    * Show title submission page
    */
    public function TitleSubmission()
    {
        if (!Auth::user()->canLeadGroup()) {
            abort(403, 'Access denied. Group leaders only.');
        }

        $submission = TitleSubmission::where('student_id', Auth::id())->first();
        $currentRequests = Auth::user()
            ->adviserRequests()
            ->currentRequest()
            ->with('adviser')
            ->latest()
            ->get();
        $activeRequest = $currentRequests->firstWhere('status', 'approved');
        $latestRejectedRequest = Auth::user()
            ->adviserRequests()
            ->where('status', 'rejected')
            ->with('adviser')
            ->latest('responded_at')
            ->latest()
            ->first();

        return view('advisers.title-submission', compact('submission', 'currentRequests', 'activeRequest', 'latestRejectedRequest'));
    }
}
