<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultipleAdviserRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_acceptance_withdraws_other_requests_and_their_notifications(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $winner = User::factory()->create(['role' => 'Teacher']);
        $other = User::factory()->create(['role' => 'Teacher']);
        $archived = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create(['title' => 'Research Group', 'owner_id' => $leader->id, 'status' => 'active']);
        $archivedRelationship = $leader->adviserRequests()->create([
            'adviser_id' => $archived->id,
            'status' => 'approved',
            'archived_at' => now()->subDay(),
        ]);

        $this->actingAs($leader);
        foreach ([$winner, $other, $archived] as $adviser) {
            $this->post(route('advisers.send-request'), ['adviser_id' => $adviser->id])
                ->assertRedirect(route('advisers.title-submission'))
                ->assertSessionDoesntHaveErrors();
        }
        $this->get(route('advisers.title-submission'))
            ->assertOk()
            ->assertSee($winner->name)
            ->assertSee($other->name)
            ->assertSee('Analyze Titles');
        Http::fake(['*/analyze' => Http::response(['analysis' => 'Expertise: IoT.'])]);
        $this->post(route('suggested-ai'), ['mode' => 'single', 'title1' => 'IoT Classroom Monitoring'])
            ->assertOk()
            ->assertViewIs('advisers.suggestedAI');
        $this->post(route('advisers.send-request'), ['adviser_id' => $other->id])
            ->assertSessionHasErrors('adviser_id');
        $this->assertSame(3, $leader->adviserRequests()->pending()->count());

        $winningRequest = $leader->adviserRequests()->where('adviser_id', $winner->id)->sole();
        $otherRequest = $leader->adviserRequests()->where('adviser_id', $other->id)->sole();
        $notificationsUrl = route('notifications.index', ['type' => 'student-requests']);
        $this->actingAs($other)->get($notificationsUrl)
            ->assertOk()
            ->assertSee('New adviser request');

        $this->actingAs($winner)->post(route('advisers.respond', $winningRequest), ['status' => 'approved'])
            ->assertSessionHas('success', 'Request approved successfully!');

        $this->assertSame($winner->id, $project->fresh()->adviser_id);
        $this->assertSame(0, $leader->adviserRequests()->pending()->count());
        $this->assertSame(1, $leader->adviserRequests()->approved()->active()->count());
        $this->assertModelMissing($otherRequest);
        $this->assertTrue($archivedRelationship->fresh()->isArchived());
        $this->assertSame('approved', $archivedRelationship->fresh()->status);
        $this->assertFalse(Cache::has("navigation-counts:{$other->id}"));
        foreach ([$otherRequest, $archivedRelationship] as $withdrawn) {
            $this->assertDatabaseMissing('app_notifications', [
                'user_id' => $withdrawn->adviser_id,
                'type' => 'student_request',
                'source_type' => 'adviser_student',
                'source_id' => $withdrawn->id,
            ]);
        }

        $this->actingAs($other)->get($notificationsUrl)
            ->assertOk()
            ->assertDontSee('New adviser request');
        $this->get(route('advisers.pending-requests'))
            ->assertOk()
            ->assertDontSee($leader->name);
        $this->post(route('advisers.respond', $otherRequest), ['status' => 'approved'])->assertNotFound();
        $this->actingAs($archived)->post(route('advisers.respond', $archivedRelationship), ['status' => 'approved'])
            ->assertSessionHas('error', 'This adviser request is no longer available.');
        $this->actingAs($leader)->post(route('advisers.send-request'), ['adviser_id' => $other->id])
            ->assertSessionHasErrors('adviser_id');
        $this->assertSame($winner->id, $project->fresh()->adviser_id);
    }
}
