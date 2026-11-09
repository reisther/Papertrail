<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_uploads_are_linked_to_the_folder_task_with_contributors(): void
    {
        Storage::fake('public');
        $leader = User::factory()->create(['firstname' => 'Group', 'lastname' => 'Leader', 'role' => 'Leader']);
        $student = User::factory()->create(['firstname' => 'File', 'lastname' => 'Uploader', 'role' => 'Student']);
        $partner = User::factory()->create(['firstname' => 'Writing', 'lastname' => 'Partner', 'role' => 'Student']);
        $outsider = User::factory()->create(['role' => 'Student']);
        $adviser = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create([
            'title' => 'Research Group',
            'owner_id' => $leader->id,
            'adviser_id' => $adviser->id,
            'status' => 'active',
        ]);
        $project->members()->attach([$student->id, $partner->id], [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $conceptTask = $project->tasks()->create([
            'adviser_id' => $adviser->id, 'chapter' => 0, 'title' => 'Draft the concept',
        ]);
        $chapterTask = $project->tasks()->create([
            'adviser_id' => $adviser->id, 'chapter' => 1, 'title' => 'Write the introduction',
        ]);
        $folder = $project->folders()->create([
            'name' => 'Concept Paper', 'created_by' => $leader->id,
        ]);
        $folderUrl = route('projects.show', ['project' => $project, 'folder' => $folder]);
        $uploadUrl = route('projects.upload-documents', $project);

        $this->actingAs($student)->get($folderUrl)
            ->assertOk()
            ->assertSee('Which to-do task are you uploading this for?')
            ->assertSee('Draft the concept')
            ->assertDontSee('Write the introduction')
            ->assertSee('File Uploader (you)');

        $upload = [
            'folder_id' => $folder->id,
            'files' => [UploadedFile::fake()->create('concept.pdf', 10, 'application/pdf')],
        ];
        $this->from($folderUrl)->post($uploadUrl, $upload)
            ->assertSessionHasErrorsIn('upload', ['project_task_id']);
        $this->post($uploadUrl, $upload + ['project_task_id' => $chapterTask->id])
            ->assertSessionHasErrorsIn('upload', ['project_task_id']);
        $this->post($uploadUrl, $upload + [
            'project_task_id' => $conceptTask->id,
            'contributor_ids' => [$outsider->id],
        ])->assertSessionHasErrorsIn('upload', ['contributor_ids.0']);
        $this->assertDatabaseCount('documents', 0);
        $this->assertFalse($conceptTask->fresh()->is_completed);

        $this->post($uploadUrl, $upload + [
            'project_task_id' => $conceptTask->id,
            'contributor_ids' => [$partner->id, $leader->id],
        ])->assertRedirect($folderUrl)->assertSessionHas('success');

        $document = $conceptTask->documents()->sole();
        $conceptTask->refresh();
        $this->assertTrue($conceptTask->is_completed);
        $this->assertNotNull($conceptTask->completed_at);
        $this->assertSame([
            $student->id => $student->name,
            $leader->id => $leader->name,
            $partner->id => $partner->name,
        ], $conceptTask->completion_contributors);
        $this->assertFalse($chapterTask->fresh()->is_completed);
        $this->assertSame(['File Uploader', 'Group Leader', 'Writing Partner'], $document->contributor_names);
        Storage::disk('public')->assertExists($document->file_path);
        $previewUrl = route('projects.preview-document', [$project, $document]);

        foreach ([$student, $leader, $adviser] as $viewer) {
            $this->actingAs($viewer)->get(route('todo.index', ['project_id' => $project->id]))
                ->assertOk()
                ->assertSee('Show concept.pdf')
                ->assertSee($previewUrl, false)
                ->assertSee('File Uploader, Group Leader, Writing Partner');
            $this->get($previewUrl)->assertOk();
        }

        $this->actingAs($adviser)->get(route('advisers.todo', ['project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Waiting Progress')
            ->assertDontSee('Waiting for leader')
            ->assertSee($previewUrl, false);
        $this->actingAs($outsider)->get($previewUrl)->assertForbidden();

        $todoUrl = route('todo.index', ['project_id' => $project->id]);
        $toggleUrl = route('todo.toggle', $conceptTask);
        $this->actingAs($leader)->get($todoUrl)
            ->assertOk()
            ->assertSee('Who did this</summary>', false)
            ->assertSee('name="completion_user_ids[]"', false);

        $this->from($todoUrl)->patch($toggleUrl, [
            'is_completed' => '1',
            'completion_user_ids' => [$student->id, $outsider->id],
        ])->assertSessionHasErrorsIn('taskCompletion', ['completion_user_ids.1']);

        $this->patch($toggleUrl, [
            'is_completed' => '1',
            'completion_user_ids' => [$student->id, $partner->id],
        ])->assertRedirect($todoUrl)->assertSessionDoesntHaveErrors();
        $this->assertSame([
            $student->id => $student->name,
            $partner->id => $partner->name,
        ], $conceptTask->fresh()->completion_contributors);
    }
}
