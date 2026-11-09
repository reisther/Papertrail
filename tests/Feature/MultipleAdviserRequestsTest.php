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
            $adviser->expertise()->create(['iot' => true]);
        }
        config()->set('services.papertrail_ai.enabled', true);
        Http::fake(['*/analyze' => Http::response(['analysis' => 'Expertise: IoT.'])]);
        $title = 'IoT Classroom Monitoring';
        $description = 'A sensor-based system for monitoring classroom conditions.';
        $results = $this->post(route('suggested-ai'), [
            'mode' => 'single',
            'title1' => $title,
            'descriptions' => ['title1' => $description],
        ])->assertOk()->assertViewIs('advisers.suggestedAI')
            ->assertSee('Edit Title')->assertSee('Select All Available')->assertSee('Send Selected Requests');
        $matchesUrl = route('advisers.matches', ['mode' => 'single']);
        $this->post(route('advisers.send-request'), [
            'mode' => 'single',
            'match_token' => $results->viewData('match')['token'],
            'adviser_ids' => [$winner->id, $other->id, $archived->id],
        ])->assertRedirect($matchesUrl)->assertSessionDoesntHaveErrors();
        $this->get($matchesUrl)->assertOk()->assertSee('Pending Approval (3)');
        Http::assertSentCount(1);

        $this->get(route('advisers.title-submission', ['mode' => 'single', 'tab' => 'pending']))
            ->assertOk()
            ->assertSee($winner->name)
            ->assertSee($other->name)
            ->assertDontSee('Analyze Title');
        $this->get(route('advisers.title-submission', ['mode' => 'single']))
            ->assertOk()->assertSee($description)->assertSee('Analyze Title');
        $revisedDescription = 'A revised description for future requests.';
        $this->post(route('suggested-ai'), [
            'mode' => 'single',
            'title1' => $title,
            'descriptions' => ['title1' => $revisedDescription],
        ])->assertOk()->assertSee($revisedDescription);
        $this->post(route('advisers.send-request'), ['adviser_id' => $other->id])
            ->assertSessionHasErrors('adviser_id');
        $this->assertSame(3, $leader->adviserRequests()->pending()->count());

        $winningRequest = $leader->adviserRequests()->where('adviser_id', $winner->id)->sole();
        $otherRequest = $leader->adviserRequests()->where('adviser_id', $other->id)->sole();
        $notificationsUrl = route('notifications.index', ['type' => 'student-requests']);
        $this->actingAs($other)->get($notificationsUrl)
            ->assertOk()
            ->assertSee('New adviser request');
        $this->get(route('advisers.pending-requests'))->assertOk()
            ->assertSee($title)->assertSee($description)->assertDontSee($revisedDescription);

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
