<?php

namespace Tests\Feature;

use App\Models\DefenseRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefenseRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_records_panel_suggestion_revision_and_page_for_one_chapter(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $adviser = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create([
            'title' => 'Defense group',
            'owner_id' => $leader->id,
            'adviser_id' => $adviser->id,
        ]);

        $this->actingAs($leader)->get(route('todo.index'))
            ->assertOk()
            ->assertSee('Defense Revisions');

        $this->post(route('defense-revisions.store'), [
            'project_id' => $project->id,
            'chapter' => 3,
            'panel_name' => 'Dr. Cruz',
            'panel_suggestion' => 'Explain the sampling method.',
        ])->assertRedirect(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]));

        $revision = DefenseRevision::sole();
        $this->assertSame($leader->id, $revision->created_by);
        $this->assertSame('Dr. Cruz', $revision->panel_name);
        $this->assertFalse($revision->is_completed);

        $this->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]))
            ->assertOk()
            ->assertSee('Chapter 3')
            ->assertSee('Panel: Dr. Cruz')
            ->assertSee('x-show="!editing"', false)
            ->assertSee('x-show="editing" style="display: none;"', false)
            ->assertSee('>Edit</button>', false)
            ->assertSee('Explain the sampling method.');

        $this->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 2]))
            ->assertOk()
            ->assertDontSee('Explain the sampling method.');

        $this->patch(route('defense-revisions.update', $revision), [
            'panel_name' => 'Dr. Cruz',
            'panel_suggestion' => 'Explain the sampling method.',
            'revision_note' => '',
            'page_reference' => '',
            'is_completed' => 1,
        ])->assertSessionHasErrors(['revision_note', 'page_reference']);

        $this->assertFalse($revision->fresh()->is_completed);

        $this->patch(route('defense-revisions.update', $revision), [
            'panel_name' => 'Prof. Reyes',
            'panel_suggestion' => 'Explain the sampling method.',
            'revision_note' => 'Added participant criteria and sample size.',
            'page_reference' => '25–26',
            'is_completed' => 1,
        ])->assertRedirect(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]));

        $revision->refresh();
        $this->assertTrue($revision->is_completed);
        $this->assertNotNull($revision->completed_at);
        $this->assertSame('Prof. Reyes', $revision->panel_name);
        $this->assertSame('25–26', $revision->page_reference);

        $this->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]))
            ->assertOk()
            ->assertSee('Panel: Prof. Reyes')
            ->assertSee('Added participant criteria and sample size.');

        $this->actingAs($adviser)
            ->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]))
            ->assertOk()
            ->assertSee('Added participant criteria and sample size.')
            ->assertSee('25–26')
            ->assertDontSee('>Edit</button>', false)
            ->assertDontSee('Delete task')
            ->assertDontSee(route('defense-revisions.update', $revision), false);

        $this->actingAs($leader)
            ->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]))
            ->assertOk()
            ->assertSee('Delete task');

        $this->delete(route('defense-revisions.destroy', $revision))
            ->assertRedirect(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 3]));

        $this->assertNull($revision->fresh());
    }

    public function test_only_the_group_leader_can_change_defense_revisions(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $otherLeader = User::factory()->create(['role' => 'Leader']);
        $member = User::factory()->create(['role' => 'Student']);
        $adviser = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create([
            'title' => 'Defense group',
            'owner_id' => $leader->id,
            'adviser_id' => $adviser->id,
        ]);
        $project->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $revision = $project->defenseRevisions()->create([
            'created_by' => $leader->id,
            'chapter' => 1,
            'panel_suggestion' => 'Add a clearer research question.',
        ]);

        foreach ([$member, $adviser, $otherLeader] as $user) {
            $this->actingAs($user)
                ->post(route('defense-revisions.store'), [
                    'project_id' => $project->id,
                    'chapter' => 1,
                    'panel_name' => 'Dr. Panel',
                    'panel_suggestion' => 'Unauthorized change',
                ])->assertForbidden();

            $this->patch(route('defense-revisions.update', $revision), [
                'panel_suggestion' => 'Unauthorized change',
                'is_completed' => 0,
            ])->assertForbidden();

            $this->delete(route('defense-revisions.destroy', $revision))->assertForbidden();
        }

        $this->actingAs($member)
            ->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 1]))
            ->assertOk()
            ->assertSee('Add a clearer research question.');

        $this->actingAs($otherLeader)
            ->get(route('defense-revisions.index', ['project_id' => $project->id]))
            ->assertNotFound();

        $this->assertSame('Add a clearer research question.', $revision->fresh()->panel_suggestion);
    }

    public function test_new_panel_suggestion_requires_a_panel_name(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $project = Project::create([
            'title' => 'Defense group',
            'owner_id' => $leader->id,
        ]);

        $this->actingAs($leader)
            ->post(route('defense-revisions.store'), [
                'project_id' => $project->id,
                'chapter' => 2,
                'panel_suggestion' => 'Clarify the related literature.',
            ])
            ->assertSessionHasErrors('panel_name');

        $this->assertDatabaseCount('defense_revisions', 0);
    }

    public function test_leader_can_start_defense_revisions_before_an_adviser_is_assigned(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $project = Project::create([
            'title' => 'Unassigned group',
            'owner_id' => $leader->id,
        ]);

        $this->actingAs($leader)
            ->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 1]))
            ->assertOk()
            ->assertSee('Unassigned group');

        $this->post(route('defense-revisions.store'), [
            'project_id' => $project->id,
            'chapter' => 1,
            'panel_name' => 'Dr. Panel',
            'panel_suggestion' => 'Define the scope of the study.',
        ])->assertRedirect();

        $this->assertDatabaseHas('defense_revisions', [
            'project_id' => $project->id,
            'chapter' => 1,
            'panel_name' => 'Dr. Panel',
            'panel_suggestion' => 'Define the scope of the study.',
        ]);
    }

    public function test_archived_group_revisions_remain_visible_but_read_only(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $project = Project::create([
            'title' => 'Archived defense group',
            'owner_id' => $leader->id,
            'status' => 'archived',
        ]);
        $project->defenseRevisions()->create([
            'created_by' => $leader->id,
            'chapter' => 1,
            'panel_suggestion' => 'Recheck the objectives.',
        ]);

        $this->actingAs($leader)
            ->get(route('defense-revisions.index', ['project_id' => $project->id, 'chapter' => 1]))
            ->assertOk()
            ->assertSee('Recheck the objectives.')
            ->assertSee('Panel: Not recorded')
            ->assertSee('Archived · view only');

        $this->post(route('defense-revisions.store'), [
            'project_id' => $project->id,
            'chapter' => 1,
            'panel_name' => 'Dr. Panel',
            'panel_suggestion' => 'Another suggestion',
        ])->assertForbidden();
    }
}
