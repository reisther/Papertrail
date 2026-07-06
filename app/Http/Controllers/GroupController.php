<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function show(Request $request): View
    {
        $user = Auth::user();

        if (!$user->isStudentGroupRole()) {
            abort(403, 'Only group members can view group details.');
        }

        $group = $user->canLeadGroup()
            ? $user->ownedProjects()
                ->with(['owner', 'adviser', 'members', 'invitations' => fn ($query) => $query->latest()])
                ->latest()
                ->first()
            : $user->joinedProjects()
                ->with(['owner', 'adviser', 'members'])
                ->latest('project_members.joined_at')
                ->first();

        $approvedAdviser = null;
        $approvedAdviserRelationship = null;
        if ($group?->adviser) {
            $approvedAdviser = $group->adviser;
            $approvedAdviserRelationship = $group->owner?->advisers()
                ->where('adviser_id', $group->adviser_id)
                ->first();
        } else {
            $leader = $user->canLeadGroup() ? $user : $group?->owner;
            $approvedAdviserRelationship = $leader?->advisers()
                ->approved()
                ->with('adviser')
                ->latest('responded_at')
                ->first();
            $approvedAdviser = $approvedAdviserRelationship?->adviser;
        }

        $activeInvitation = null;
        if ($user->canLeadGroup()) {
            $activeInvitation = $group?->invitations()
                ->whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->first();
        }

        $canManageGroup = $user->canLeadGroup();
        $isEditing = $canManageGroup && (!$group || $request->boolean('edit'));

        return view('groups.description', compact('group', 'activeInvitation', 'canManageGroup', 'isEditing', 'approvedAdviser', 'approvedAdviserRelationship'));
    }

    public function details(Project $project): View
    {
        $user = Auth::user();

        $canViewPendingAdviserRequest = $user->isTeacher()
            && $user->studentRequests()
                ->where('student_id', $project->owner_id)
                ->exists();

        if (!$project->canAccess($user) && !$canViewPendingAdviserRequest) {
            abort(403, 'You cannot view this group.');
        }

        $group = $project->load(['owner', 'adviser', 'members']);
        $approvedAdviser = $group->adviser;
        $approvedAdviserRelationship = $group->owner?->advisers()
            ->where('adviser_id', $group->adviser_id)
            ->first();

        if (!$approvedAdviserRelationship) {
            $approvedAdviserRelationship = $group->owner?->advisers()
                ->approved()
                ->with('adviser')
                ->when($user->isTeacher(), fn ($query) => $query->where('adviser_id', $user->id))
                ->latest('responded_at')
                ->first();
            $approvedAdviser = $approvedAdviserRelationship?->adviser;
        }

        $activeInvitation = null;
        $canManageGroup = $group->canEdit($user);
        $isEditing = false;

        return view('groups.description', compact('group', 'activeInvitation', 'canManageGroup', 'isEditing', 'approvedAdviser', 'approvedAdviserRelationship'));
    }

    public function update(Request $request): RedirectResponse
    {
        $leader = Auth::user();

        if (!$leader->canLeadGroup()) {
            abort(403, 'Only group leaders can manage group details.');
        }

        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'group_course' => 'required|in:Information Technology,Information Systems,Computer Science',
            'group_description' => 'nullable|string|max:2000',
        ]);

        $approvedAdviserId = $leader->advisers()
            ->approved()
            ->latest('responded_at')
            ->value('adviser_id');

        $group = Project::updateOrCreate(
            ['owner_id' => $leader->id],
            [
                'title' => $validated['group_name'],
                'group_course' => $validated['group_course'],
                'description' => $validated['group_description'],
                'adviser_id' => $approvedAdviserId,
                'status' => 'active',
            ]
        );

        $group->syncCourseTasksFromAdviser();

        return back()->with('success', 'Group details updated successfully.');
    }

    public function shareLink(): RedirectResponse
    {
        $leader = Auth::user();

        if (!$leader->canLeadGroup()) {
            abort(403, 'Only group leaders can share group links.');
        }

        $group = $leader->ownedProjects()->latest()->first();

        if (!$group) {
            return back()->with('error', 'Create your group details first before sharing a link.');
        }

        $invitation = ProjectInvitation::create([
            'project_id' => $group->id,
            'token' => Str::random(48),
            'created_by' => $leader->id,
            'expires_at' => now()->addDays(14),
        ]);

        return back()
            ->with('success', 'Group invitation link is ready to share with student members only.')
            ->with('invite_link', route('projects.accept-invitation', $invitation->token));
    }

    public function removeMember(User $member): RedirectResponse
    {
        $leader = Auth::user();

        if (!$leader->canLeadGroup()) {
            abort(403, 'Only group leaders can remove group members.');
        }

        $group = $leader->ownedProjects()->latest()->firstOrFail();

        $group->members()->detach($member->id);

        $projectChat = ChatRoom::where('project_id', $group->id)
            ->where('type', 'project')
            ->first();

        if ($projectChat) {
            $projectChat->participants()->detach($member->id);
        }

        return back()->with('success', "{$member->name} was removed from the group.");
    }
}
