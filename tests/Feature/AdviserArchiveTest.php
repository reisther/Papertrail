<?php

namespace Tests\Feature;

use App\Models\AdviserStudent;
use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdviserArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_group_shows_group_details_action(): void
    {
        [$leader, $adviser, $group] = $this->archivedAdviserGroup();

        $response = $this
            ->actingAs($adviser)
            ->get(route('advisers.my-students'));

        $response
            ->assertOk()
            ->assertSee('Archived History')
            ->assertSee('Group Details')
            ->assertSee(route('group-description.details', $group), false);
    }

    public function test_archived_adviser_shows_archived_chats_action_to_student_leader(): void
    {
        [$leader, $adviser, $group] = $this->archivedAdviserGroup();
        $chatRoom = ChatRoom::create([
            'name' => 'PaperTrail Group Chat',
            'description' => 'Archived adviser chat.',
            'type' => 'project',
            'project_id' => $group->id,
            'created_by' => $leader->id,
            'is_active' => true,
        ]);

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($adviser->id, [
            'role' => 'moderator',
            'joined_at' => now(),
        ]);

        $response = $this
            ->actingAs($leader)
            ->get(route('advisers.my-advisers'));

        $response
            ->assertOk()
            ->assertSee('Archived Adviser')
            ->assertSee('Archived chats')
            ->assertSee(route('chat.archived', ['room' => $chatRoom->id]), false);
    }

    public function test_archived_chat_messages_can_be_loaded(): void
    {
        [$leader, $adviser, $group] = $this->archivedAdviserGroup();
        $chatRoom = ChatRoom::create([
            'name' => 'Archived Chat',
            'description' => 'Saved adviser conversation.',
            'type' => 'project',
            'project_id' => $group->id,
            'created_by' => $leader->id,
            'is_active' => true,
        ]);

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($adviser->id, [
            'role' => 'moderator',
            'joined_at' => now(),
        ]);
        $chatRoom->messages()->create([
            'user_id' => $leader->id,
            'message' => 'Hi',
            'message_type' => 'text',
        ]);

        $this
            ->actingAs($leader)
            ->getJson(route('chat.rooms.messages', $chatRoom))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Hi')
            ->assertJsonPath('messages.0.is_archived', true)
            ->assertJsonPath('messages.0.can_edit', false);
    }

    public function test_same_adviser_request_restores_archived_group_when_approved(): void
    {
        [$leader, $adviser, $group] = $this->archivedAdviserGroup();

        $this
            ->actingAs($leader)
            ->post(route('advisers.send-request'), [
                'adviser_id' => $adviser->id,
                'message' => 'Please advise us again.',
            ])
            ->assertRedirect(route('advisers.title-submission'));

        $relationship = AdviserStudent::sole();

        $this->assertSame('pending', $relationship->status);
        $this->assertNotNull($relationship->archived_at);

        $this
            ->actingAs($adviser)
            ->post(route('advisers.respond', $relationship), [
                'status' => 'approved',
                'response_message' => 'Welcome back.',
            ])
            ->assertSessionHas('success', 'Request approved successfully!');

        $relationship->refresh();

        $this->assertSame('approved', $relationship->status);
        $this->assertNull($relationship->archived_at);
        $this->assertSame($adviser->id, $group->refresh()->adviser_id);
        $this->assertSame(1, AdviserStudent::count());
    }

    public function test_rejected_reactivation_request_keeps_group_archived(): void
    {
        [$leader, $adviser, $group] = $this->archivedAdviserGroup();

        $this
            ->actingAs($leader)
            ->post(route('advisers.send-request'), [
                'adviser_id' => $adviser->id,
                'message' => 'Please advise us again.',
            ]);

        $relationship = AdviserStudent::sole();

        $this
            ->actingAs($adviser)
            ->post(route('advisers.respond', $relationship), [
                'status' => 'rejected',
                'response_message' => 'Not available.',
            ])
            ->assertSessionHas('success', 'Request rejected. The group remains in archived history.');

        $relationship->refresh();

        $this->assertSame('approved', $relationship->status);
        $this->assertNotNull($relationship->archived_at);
        $this->assertNull($group->refresh()->adviser_id);
    }

    private function archivedAdviserGroup(): array
    {
        $leader = User::factory()->create([
            'role' => 'Leader',
            'course' => 'Information Technology',
            'section' => 'IT-4A',
        ]);
        $member = User::factory()->create([
            'role' => 'Student',
            'course' => 'Information Technology',
            'section' => 'IT-4A',
        ]);
        $adviser = User::factory()->create([
            'role' => 'Teacher',
            'course' => 'Information Technology',
        ]);

        $group = Project::create([
            'title' => 'PaperTrail Group',
            'description' => 'A group description that should stay visible.',
            'group_course' => 'Information Technology',
            'owner_id' => $leader->id,
            'adviser_id' => null,
            'status' => 'active',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        AdviserStudent::create([
            'student_id' => $leader->id,
            'adviser_id' => $adviser->id,
            'status' => 'approved',
            'message' => 'Original request.',
            'response_message' => 'Approved.',
            'responded_at' => now()->subMonth(),
            'archived_at' => now()->subDay(),
        ]);

        return [$leader, $adviser, $group];
    }
}
