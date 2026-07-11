<?php

namespace Tests\Feature;

use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_chat_participant_is_not_returned_in_room_names_or_mentions(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $chatRoom = $this->projectChatRoom($group, 'Research Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($leader)
            ->deleteJson(route('chat.rooms.remove-participant', $chatRoom), [
                'user_id' => $member->id,
            ])
            ->assertOk();

        $response = $this
            ->actingAs($leader)
            ->getJson(route('chat.rooms.show', $chatRoom))
            ->assertOk();

        $response->assertJsonMissing([
            'id' => $member->id,
            'name' => $member->firstname . ' ' . $member->lastname,
        ]);

        $this->assertFalse($chatRoom->fresh()->hasParticipant($member));
    }

    public function test_group_member_removal_detaches_member_from_all_project_chat_rooms(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $firstRoom = $this->projectChatRoom($group, 'Project Chat');
        $secondRoom = $this->projectChatRoom($group, 'Planning Chat');

        foreach ([$firstRoom, $secondRoom] as $chatRoom) {
            $chatRoom->participants()->attach($leader->id, [
                'role' => 'admin',
                'joined_at' => now(),
            ]);
            $chatRoom->participants()->attach($member->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        $this
            ->actingAs($leader)
            ->delete(route('group-description.members.remove', $member))
            ->assertSessionHas('success');

        foreach ([$firstRoom, $secondRoom] as $chatRoom) {
            $this->assertFalse($chatRoom->fresh()->hasParticipant($member));
        }
    }

    private function groupWithMember(): array
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

        $group = Project::create([
            'title' => 'PaperTrail Group',
            'description' => 'Research group',
            'group_course' => 'Information Technology',
            'owner_id' => $leader->id,
            'status' => 'active',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return [$leader, $member, $group];
    }

    private function projectChatRoom(Project $group, string $name): ChatRoom
    {
        return ChatRoom::create([
            'name' => $name,
            'description' => 'Group chat room',
            'type' => 'project',
            'project_id' => $group->id,
            'created_by' => $group->owner_id,
            'is_active' => true,
        ]);
    }
}
