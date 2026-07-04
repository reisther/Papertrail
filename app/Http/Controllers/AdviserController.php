<?php

namespace App\Http\Controllers;

use App\Models\AdviserStudent;
use App\Models\AppNotification;
use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->whereIn('status', ['pending', 'approved'])
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

        $request->validate([
            'adviser_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        $activeRequest = Auth::user()->adviserRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($activeRequest) {
            return redirect()
                ->route('advisers.title-submission')
                ->withErrors(['adviser_id' => 'You already have an active adviser request. Wait for a response before sending another request.']);
        }

        // Check if adviser is actually a teacher
        $adviser = User::findOrFail($request->adviser_id);
        if (!$adviser->isTeacher()) {
            return redirect()
                ->route('advisers.title-submission')
                ->withErrors(['adviser_id' => 'Selected user is not a teacher.']);
        }

        // Check if request already exists
        $existingRequest = AdviserStudent::where('student_id', Auth::id())
            ->where('adviser_id', $request->adviser_id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingRequest) {
            return redirect()
                ->route('advisers.title-submission')
                ->withErrors(['adviser_id' => 'You already have a request with this adviser.']);
        }

        $adviserRequest = AdviserStudent::create([
            'student_id' => Auth::id(),
            'adviser_id' => $request->adviser_id,
            'message' => $request->message,
            'status' => 'pending'
        ]);
        if (Schema::hasTable('app_notifications')) {
            AppNotification::firstOrCreate(
                [
                    'user_id' => $adviser->id,
                    'type' => 'student_request',
                    'source_type' => 'adviser_student',
                    'source_id' => $adviserRequest->id,
                ],
                [
                    'title' => 'New adviser request',
                    'body' => Auth::user()->name . ' sent you an adviser request.',
                    'action_url' => route('advisers.pending-requests'),
                ]
            );
        }
        app(EmailNotificationService::class)->sendAdviserRequestReceived($adviserRequest);

        return redirect()
            ->route('advisers.title-submission')
            ->with('success', 'Request sent to ' . $adviser->name . ' successfully!');
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

        $adviserStudent->update([
            'status' => $request->status,
            'response_message' => $request->response_message,
            'responded_at' => now()
        ]);

        if ($request->status === 'approved') {
            Project::where('owner_id', $adviserStudent->student_id)
                ->update(['adviser_id' => $adviserStudent->adviser_id]);

            ChatRoom::whereIn('project_id', Project::where('owner_id', $adviserStudent->student_id)->pluck('id'))
                ->where('type', 'project')
                ->get()
                ->each(fn (ChatRoom $room) => $room->addParticipant($adviserStudent->adviser, 'moderator'));
        }

        $statusText = $request->status === 'approved' ? 'approved' : 'rejected';
        return back()->with('success', "Request {$statusText} successfully!");
    }

    public function removeRequest(AdviserStudent $adviserStudent)
    {
        if ($adviserStudent->student_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }

        $adviserName = $adviserStudent->adviser?->name ?? 'The adviser';

        if ($adviserStudent->status === 'approved') {
            $projectIds = Project::where('owner_id', $adviserStudent->student_id)
                ->where('adviser_id', $adviserStudent->adviser_id)
                ->pluck('id');

            Project::whereIn('id', $projectIds)->update(['adviser_id' => null]);

            ChatRoom::whereIn('project_id', $projectIds)
                ->where('type', 'project')
                ->get()
                ->each(fn (ChatRoom $room) => $room->participants()->detach($adviserStudent->adviser_id));
        }

        $adviserStudent->delete();

        return redirect()
            ->route('advisers.title-submission')
            ->with('success', "Request to {$adviserName} removed successfully.");
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

        $projectIds = Project::where('owner_id', $adviserStudent->student_id)
            ->where('adviser_id', $adviserStudent->adviser_id)
            ->pluck('id');

        Project::whereIn('id', $projectIds)->update(['adviser_id' => null]);

        ChatRoom::whereIn('project_id', $projectIds)
            ->where('type', 'project')
            ->get()
            ->each(fn (ChatRoom $room) => $room->participants()->detach($adviserStudent->adviser_id));

        $adviserName = $adviserStudent->adviser?->name ?? 'The adviser';
        $adviserStudent->delete();

        return back()->with('success', "{$adviserName} is no longer assigned as adviser.");
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

        $adviserStudent->update(['archived_at' => now()]);

        return back()->with('success', 'Group archived from your active adviser list.');
    }

    /**
     * Show my advisers (for students)
     */
    public function myAdvisers()
    {
        if (!Auth::user()->canLeadGroup()) {
            abort(403, 'Access denied. Group leaders only.');
        }

        $advisers = Auth::user()->advisers()->with('adviser')->get();
        
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
        
        return view('advisers.my-students', compact('students'));
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
        $activeRequest = Auth::user()
            ->adviserRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->with('adviser')
            ->latest()
            ->first();
        $latestRejectedRequest = Auth::user()
            ->adviserRequests()
            ->where('status', 'rejected')
            ->with('adviser')
            ->latest('responded_at')
            ->latest()
            ->first();

        return view('advisers.title-submission', compact('submission', 'activeRequest', 'latestRejectedRequest'));
    }
}
