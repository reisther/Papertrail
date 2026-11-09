<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApprovedTitleAdviserMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_send_one_approved_title_to_the_existing_ai_matcher(): void
    {
        config()->set('services.papertrail_ai.enabled', true);
        Http::fake(['*/analyze' => Http::response(['analysis' => 'Expertise: IoT.'])]);
        $leader = User::factory()->create(['role' => 'Leader']);
        Project::create(['title' => 'Research Group', 'owner_id' => $leader->id, 'status' => 'active']);
        $title = 'IoT Classroom Monitoring';

        $this->actingAs($leader)->get(route('advisers.title-submission'))
            ->assertOk()
            ->assertSee('Use an Approved Title')
            ->assertSee('name="title5"', false);

        $this->get(route('advisers.title-submission', ['mode' => 'single']))
            ->assertOk()
            ->assertSee('Approved Research Title')
            ->assertSee('name="title1"', false)
            ->assertDontSee('name="title2"', false);

        $this->post(route('suggested-ai'), ['mode' => 'single', 'title1' => $title])
            ->assertOk()
            ->assertViewIs('advisers.suggestedAI');

        Http::assertSent(fn ($request) => $request->data() === ['title1' => $title]);
    }
}
