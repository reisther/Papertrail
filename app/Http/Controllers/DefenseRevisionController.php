<?php

namespace App\Http\Controllers;

use App\Models\DefenseRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DefenseRevisionController extends Controller
{
    private const STAGES = [
        0 => 'Concept Paper',
        1 => 'Chapter 1',
        2 => 'Chapter 2',
        3 => 'Chapter 3',
        4 => 'Chapter 4',
        5 => 'Chapter 5',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudentGroupRole() || $user->isTeacher(), 403);

        $projects = $user->accessibleProjects()
            ->with('owner')
            ->orderBy('title')
            ->get();

        $selectedProject = $request->filled('project_id')
            ? $projects->firstWhere('id', $request->integer('project_id'))
            : $projects->first();

        if ($request->filled('project_id')) {
            abort_unless($selectedProject, 404);
        }

        $selectedChapter = $request->has('chapter') ? $request->integer('chapter') : 1;
        if (! array_key_exists($selectedChapter, self::STAGES)) {
            $selectedChapter = 1;
        }

        $revisions = $selectedProject
            ? $selectedProject->defenseRevisions()
                ->with('creator')
                ->where('chapter', $selectedChapter)
                ->oldest()
                ->get()
            : collect();

        $chapterCounts = $selectedProject
            ? $selectedProject->defenseRevisions()
                ->selectRaw('chapter, COUNT(*) as total')
                ->groupBy('chapter')
                ->pluck('total', 'chapter')
            : collect();

        return view('defense-revisions.index', [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'selectedChapter' => $selectedChapter,
            'stages' => self::STAGES,
            'revisions' => $revisions,
            'chapterCounts' => $chapterCounts,
            'canManage' => $selectedProject && $this->canManage($user, $selectedProject),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'chapter' => ['required', 'integer', Rule::in(array_keys(self::STAGES))],
            'panel_name' => ['required', 'string', 'max:150'],
            'panel_suggestion' => ['required', 'string', 'max:5000'],
        ]);

        $project = Project::findOrFail($validated['project_id']);
        abort_unless($this->canManage($request->user(), $project), 403);

        $project->defenseRevisions()->create([
            'created_by' => $request->user()->id,
            'chapter' => $validated['chapter'],
            'panel_name' => $validated['panel_name'],
            'panel_suggestion' => $validated['panel_suggestion'],
        ]);

        return $this->backToChapter($project, (int) $validated['chapter'])
            ->with('success', 'Panel suggestion added to the revision checklist.');
    }

    public function update(Request $request, DefenseRevision $defenseRevision): RedirectResponse
    {
        $project = $defenseRevision->project;
        abort_unless($project && $this->canManage($request->user(), $project), 403);

        $completed = $request->boolean('is_completed');
        $validated = $request->validate([
            'panel_name' => ['required', 'string', 'max:150'],
            'panel_suggestion' => ['required', 'string', 'max:5000'],
            'revision_note' => [$completed ? 'required' : 'nullable', 'string', 'max:5000'],
            'page_reference' => [$completed ? 'required' : 'nullable', 'string', 'max:100'],
            'is_completed' => ['required', 'boolean'],
        ]);

        $defenseRevision->update([
            'panel_name' => $validated['panel_name'],
            'panel_suggestion' => $validated['panel_suggestion'],
            'revision_note' => $validated['revision_note'] ?? null,
            'page_reference' => $validated['page_reference'] ?? null,
            'is_completed' => $completed,
            'completed_at' => $completed ? ($defenseRevision->completed_at ?? now()) : null,
        ]);

        return $this->backToChapter($project, $defenseRevision->chapter)
            ->with('success', 'Defense revision updated.');
    }

    public function destroy(Request $request, DefenseRevision $defenseRevision): RedirectResponse
    {
        $project = $defenseRevision->project;
        abort_unless($project && $this->canManage($request->user(), $project), 403);

        $chapter = $defenseRevision->chapter;
        $defenseRevision->delete();

        return $this->backToChapter($project, $chapter)
            ->with('success', 'Defense revision removed.');
    }

    private function canManage(User $user, Project $project): bool
    {
        return $user->canLeadGroup()
            && $project->owner_id === $user->id
            && ! $project->isArchivedForUser($user);
    }

    private function backToChapter(Project $project, int $chapter): RedirectResponse
    {
        return to_route('defense-revisions.index', [
            'project_id' => $project->id,
            'chapter' => $chapter,
        ]);
    }
}
