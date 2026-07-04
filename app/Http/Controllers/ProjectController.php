<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects
     */
    public function index()
    {
        $projects = Auth::user()->accessibleProjects()
            ->with(['owner', 'adviser'])
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project
     */
    public function create()
    {
        if (!Auth::user()->canLeadGroup() && !Auth::user()->isAdmin()) {
            abort(403, 'Only group leaders can create projects.');
        }

        return view('projects.create');
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        if (!Auth::user()->canLeadGroup() && !Auth::user()->isAdmin()) {
            abort(403, 'Only group leaders can create projects.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'owner_id' => Auth::id(),
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'status' => 'draft',
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project
     */
    public function show(Project $project, Request $request)
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403, 'Access denied.');
        }

        $folderId = $request->get('folder');
        $currentFolder = null;
        
        if ($folderId) {
            $currentFolder = Folder::findOrFail($folderId);
            if ($currentFolder->project_id !== $project->id) {
                abort(404);
            }
        }

        // Get folders and documents for current location
        if ($currentFolder) {
            $folders = $currentFolder->children()->orderBy('name')->get();
            $documents = $currentFolder->documents()->with('uploader')->orderBy('name')->get();
            $breadcrumb = $currentFolder->breadcrumb;
        } else {
            $folders = $project->rootFolders()->orderBy('name')->get();
            $documents = $project->rootDocuments()->with('uploader')->orderBy('name')->get();
            $breadcrumb = [];
        }

        return view('projects.show', compact('project', 'folders', 'documents', 'currentFolder', 'breadcrumb'));
    }

    /**
     * Show the form for editing the project
     */
    public function edit(Project $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Access denied.');
        }

        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, Project $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,archived',
        ]);

        $project->update($request->only([
            'title', 'description', 'start_date', 'due_date', 'notes', 'status'
        ]));

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified project
     */
    public function destroy(Project $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Access denied.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    /**
     * Create a new folder
     */
    public function createFolder(Request $request, Project $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:folders,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Verify parent folder belongs to this project
        if ($request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id);
            if ($parentFolder->project_id !== $project->id) {
                abort(400, 'Invalid parent folder.');
            }
        }

        Folder::create([
            'name' => $request->name,
            'description' => $request->description,
            'project_id' => $project->id,
            'parent_id' => $request->parent_id,
            'created_by' => Auth::id(),
            'color' => $request->color ?? '#3B82F6',
        ]);

        return back()->with('success', 'Folder created successfully!');
    }

    /**
     * Generate an invite link for student members to join this project
     */
    public function generateInvitation(Project $project)
    {
        if (!$project->canInviteMembers(Auth::user())) {
            abort(403, 'Only group leaders can invite members.');
        }

        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'token' => Str::random(48),
            'created_by' => Auth::id(),
            'expires_at' => now()->addDays(14),
        ]);

        return back()
            ->with('success', 'Invitation link created. Send this link to student members only.')
            ->with('invite_link', route('projects.accept-invitation', $invitation->token));
    }

    public function showInvitation(string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->with(['project.owner', 'project.members'])
            ->firstOrFail();

        if (!$invitation->isActive()) {
            abort(403, 'This invitation link is no longer active.');
        }

        $project = $invitation->project;
        $user = Auth::user();

        if ($user->role !== 'Student') {
            abort(403, 'Only student members can accept group invitation links. Leaders and advisers cannot join through invite links.');
        }

        $alreadyInGroup = $project->owner_id === $user->id || $project->members()->where('users.id', $user->id)->exists();
        $alreadyInAnotherGroup = $user->joinedProjects()
            ->where('projects.id', '!=', $project->id)
            ->exists();

        if ($alreadyInAnotherGroup) {
            abort(403, 'You are already a member of another group.');
        }

        return view('projects.invitation', compact('invitation', 'project', 'alreadyInGroup'));
    }

    /**
     * Accept a project invite link
     */
    public function acceptInvitation(string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)->with('project')->firstOrFail();

        if (!$invitation->isActive()) {
            abort(403, 'This invitation link is no longer active.');
        }

        $project = $invitation->project;
        $user = Auth::user();

        if ($user->role !== 'Student') {
            abort(403, 'Only student members can accept group invitation links. Leaders and advisers cannot join through invite links.');
        }

        $alreadyInAnotherGroup = $user->joinedProjects()
            ->where('projects.id', '!=', $project->id)
            ->exists();

        if ($alreadyInAnotherGroup) {
            abort(403, 'You are already a member of another group.');
        }

        if ($project->owner_id !== $user->id && !$project->members()->where('users.id', $user->id)->exists()) {
            $project->members()->attach($user->id, [
                'role' => 'member',
                'invited_by' => $invitation->created_by,
                'joined_at' => now(),
            ]);

            $projectChat = ChatRoom::where('project_id', $project->id)
                ->where('type', 'project')
                ->first();

            if ($projectChat) {
                $projectChat->addParticipant($user, 'member');
            }
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'You joined the group project successfully.');
    }

    public function declineInvitation(string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)->with('project')->firstOrFail();

        if (!$invitation->isActive()) {
            abort(403, 'This invitation link is no longer active.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Group invitation declined.');
    }

    /**
     * Upload documents
     */
    public function uploadDocuments(Request $request, Project $project)
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        // Verify folder belongs to this project
        if ($request->folder_id) {
            $folder = Folder::findOrFail($request->folder_id);
            if ($folder->project_id !== $project->id) {
                abort(400, 'Invalid folder.');
            }
        }

        $uploadedCount = 0;

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('projects/' . $project->id, $fileName, 'public');

            Document::create([
                'name' => pathinfo($originalName, PATHINFO_FILENAME),
                'original_name' => $originalName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'project_id' => $project->id,
                'folder_id' => $request->folder_id,
                'uploaded_by' => Auth::id(),
            ]);

            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} file(s) uploaded successfully!");
    }

    /**
     * Preview a document
     */
    public function previewDocument(Project $project, Document $document)
    {
        if (!$project->canAccess(Auth::user()) || !$document->canAccess(Auth::user())) {
            abort(403, 'Access denied.');
        }

        if ($document->project_id !== $project->id) {
            abort(404);
        }

        if (!$document->fileExists()) {
            abort(404, 'File not found.');
        }

        $document->markAsAccessed();

        $filePath = $document->full_path;
        $mimeType = $document->mime_type;

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"'
        ]);
    }

    /**
     * Download a document
     */
    public function downloadDocument(Project $project, Document $document)
    {
        if (!$project->canAccess(Auth::user()) || !$document->canAccess(Auth::user())) {
            abort(403, 'Access denied.');
        }

        if ($document->project_id !== $project->id) {
            abort(404);
        }

        if (!$document->fileExists()) {
            abort(404, 'File not found.');
        }

        $document->markAsAccessed();

        return response()->download($document->full_path, $document->original_name);
    }

    /**
     * Delete a document
     */
    public function deleteDocument(Project $project, Document $document)
    {
        if (!$document->canDelete(Auth::user())) {
            abort(403, 'Access denied.');
        }

        if ($document->project_id !== $project->id) {
            abort(404);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully!');
    }

    /**
     * Delete a folder
     */
    public function deleteFolder(Project $project, Folder $folder)
    {
        if (!$folder->canEdit(Auth::user())) {
            abort(403, 'Access denied.');
        }

        if ($folder->project_id !== $project->id) {
            abort(404);
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted successfully!');
    }
}
