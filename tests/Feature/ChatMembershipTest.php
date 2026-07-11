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

    public function test_project_chat_names_include_group_members_except_users_removed_from_that_room(): void
    {
        [$leader, $removedMember, $group] = $this->groupWithMember();
        $currentMember = User::factory()->create([
            'role' => 'Student',
            'course' => 'Information Technology',
            'section' => 'IT-4A',
        ]);
        $group->members()->attach($currentMember->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $chatRoom = $this->projectChatRoom($group, 'Team Names Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($removedMember->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($leader)
            ->deleteJson(route('chat.rooms.remove-participant', $chatRoom), [
                'user_id' => $removedMember->id,
            ])
            ->assertOk();

        $response = $this
            ->actingAs($leader)
            ->getJson(route('chat.rooms.show', $chatRoom))
            ->assertOk();

        $response
            ->assertJsonFragment([
                'id' => $currentMember->id,
                'name' => $currentMember->firstname . ' ' . $currentMember->lastname,
            ])
            ->assertJsonMissing([
                'id' => $removedMember->id,
                'name' => $removedMember->firstname . ' ' . $removedMember->lastname,
            ]);
    }

    public function test_system_messages_do_not_create_unread_chat_badges(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $chatRoom = $this->projectChatRoom($group, 'System Message Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'last_read_at' => now()->subDay(),
        ]);
        $chatRoom->participants()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
            'last_read_at' => now()->subDay(),
        ]);

        $chatRoom->messages()->create([
            'user_id' => $leader->id,
            'message' => 'This adviser chat has been archived. The conversation is now read-only.',
            'message_type' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $chatRoom->getUnreadCountForUser($member));

        $chatRooms = $this
            ->actingAs($member)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertDontSee('<span class="chat-nav-unread-count', false)
            ->viewData('chatRooms');

        $this->assertSame(0, $chatRooms->firstWhere('id', $chatRoom->id)->unread_count);
    }

    public function test_real_messages_create_unread_badge_for_visible_project_chat(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $chatRoom = $this->projectChatRoom($group, 'Unread Message Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $chatRoom->messages()->create([
            'user_id' => $leader->id,
            'message' => 'Please check the latest draft.',
            'message_type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<span class="chat-nav-unread-count', false);

        $chatRooms = $this
            ->actingAs($member)
            ->get(route('chat.index'))
            ->assertOk()
            ->viewData('chatRooms');

        $this->assertSame(1, $chatRooms->firstWhere('id', $chatRoom->id)->unread_count);
    }

    public function test_sending_chat_message_creates_unread_badge_for_room_users(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $chatRoom = $this->projectChatRoom($group, 'Sent Message Badge Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $this
            ->actingAs($leader)
            ->postJson(route('chat.rooms.send-message', $chatRoom), [
                'message' => 'New message for the group.',
            ])
            ->assertOk();

        $this
            ->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<span class="chat-nav-unread-count', false);
    }

    public function test_unread_summary_updates_after_room_messages_are_loaded(): void
    {
        [$leader, $member, $group] = $this->groupWithMember();
        $chatRoom = $this->projectChatRoom($group, 'Live Summary Chat');

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $chatRoom->messages()->create([
            'user_id' => $leader->id,
            'message' => 'Live unread summary message.',
            'message_type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($member)
            ->getJson(route('chat.unread-summary'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath("rooms.{$chatRoom->id}", 1);

        $this
            ->actingAs($member)
            ->getJson(route('chat.rooms.messages', $chatRoom))
            ->assertOk();

        $this
            ->actingAs($member)
            ->getJson(route('chat.unread-summary'))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath("rooms.{$chatRoom->id}", 0);

        $this
            ->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('<span class="chat-nav-unread-count', false);
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
