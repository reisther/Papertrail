<?php

namespace Tests\Feature;

use App\Models\AdviserStudent;
use App\Models\ChatRoom;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function test_archived_adviser_chat_stays_visible_in_regular_leader_chats(): void
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
            ->assertDontSee('Archived chats')
            ->assertDontSee(route('chat.archived', ['room' => $chatRoom->id]), false);

        $this
            ->actingAs($leader)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('PaperTrail Group Chat')
            ->assertSee('Archived');
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

    public function test_archived_old_group_chat_does_not_follow_member_into_another_group(): void
    {
        [$leader, $adviser, $oldGroup, $member] = $this->archivedAdviserGroup();
        $oldChatRoom = ChatRoom::create([
            'name' => 'Old Archived Group Chat',
            'description' => 'Old group adviser conversation.',
            'type' => 'project',
            'project_id' => $oldGroup->id,
            'created_by' => $leader->id,
            'is_active' => true,
        ]);

        $oldChatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $oldChatRoom->participants()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $oldChatRoom->participants()->attach($adviser->id, [
            'role' => 'moderator',
            'joined_at' => now(),
        ]);

        $oldGroup->members()->detach($member->id);

        $newLeader = User::factory()->create([
            'role' => 'Leader',
            'course' => 'Information Technology',
            'section' => 'IT-4A',
        ]);
        $newGroup = Project::create([
            'title' => 'New Active Group',
            'description' => 'A new group for this member.',
            'group_course' => 'Information Technology',
            'owner_id' => $newLeader->id,
            'status' => 'active',
        ]);
        $newGroup->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($member)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('New Active Group - Project Chat')
            ->assertDontSee('Old Archived Group Chat');

        $this
            ->actingAs($member)
            ->get(route('chat.archived'))
            ->assertOk()
            ->assertDontSee('Old Archived Group Chat');
    }

    public function test_archived_group_chat_stays_visible_for_member_who_is_still_in_group(): void
    {
        [$leader, $adviser, $group, $member] = $this->archivedAdviserGroup();
        $chatRoom = ChatRoom::create([
            'name' => 'Still Member Archived Chat',
            'description' => 'Archived adviser chat for current group members.',
            'type' => 'project',
            'project_id' => $group->id,
            'created_by' => $leader->id,
            'is_active' => true,
        ]);

        $chatRoom->participants()->attach($leader->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $chatRoom->participants()->attach($adviser->id, [
            'role' => 'moderator',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($member)
            ->get(route('chat.archived'))
            ->assertOk()
            ->assertSee('Still Member Archived Chat');

        $this
            ->actingAs($member)
            ->getJson(route('chat.rooms.show', $chatRoom))
            ->assertOk()
            ->assertJsonPath('chat_room.is_archived', true);
    }

    public function test_leader_chat_page_does_not_show_archived_chats_button(): void
    {
        [$leader] = $this->archivedAdviserGroup();

        $this
            ->actingAs($leader)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertDontSee(route('chat.archived'), false);
    }

    public function test_archived_group_details_still_show_the_adviser(): void
    {
        [$leader, $adviser] = $this->archivedAdviserGroup();

        $this
            ->actingAs($leader)
            ->get(route('group-description.show'))
            ->assertOk()
            ->assertSee($adviser->name);
    }

    public function test_archived_group_files_are_read_only_for_students(): void
    {
        Storage::fake('public');

        [$leader, , $group] = $this->archivedAdviserGroup();

        $this
            ->actingAs($leader)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee($group->title)
            ->assertSee('Archived')
            ->assertDontSee(route('projects.archived'), false);

        $this
            ->actingAs($leader)
            ->get(route('projects.show', $group))
            ->assertOk()
            ->assertSee('Archived files')
            ->assertDontSee('Upload Files');

        $this
            ->actingAs($leader)
            ->post(route('projects.upload-documents', $group), [
                'files' => [UploadedFile::fake()->create('chapter-5.pdf', 100, 'application/pdf')],
            ])
            ->assertForbidden();
    }

    public function test_archived_group_files_are_visible_to_members_in_projects(): void
    {
        [$leader, , $group, $member] = $this->archivedAdviserGroup();

        $group->update(['status' => 'archived']);

        $this
            ->actingAs($member)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee($group->title)
            ->assertSee('Archived')
            ->assertDontSee(route('projects.archived'), false);

        $this
            ->actingAs($member)
            ->get(route('projects.show', $group))
            ->assertOk()
            ->assertSee('Archived files')
            ->assertDontSee('Upload Files');
    }

    public function test_same_adviser_request_restores_archived_group_when_approved(): void
    {
        [$leader, $adviser, $group, $member] = $this->archivedAdviserGroup();
        $chatRoom = ChatRoom::create([
            'name' => 'Reactivated Adviser Chat',
            'description' => 'Archived chat that will be reactivated.',
            'type' => 'project',
            'project_id' => $group->id,
            'created_by' => $leader->id,
            'is_active' => true,
        ]);
        $oldReadAt = now()->subDays(2);

        foreach ([$leader, $member, $adviser] as $participant) {
            $chatRoom->participants()->attach($participant->id, [
                'role' => $participant->id === $leader->id ? 'admin' : ($participant->id === $adviser->id ? 'moderator' : 'member'),
                'joined_at' => now()->subDays(5),
                'last_read_at' => $oldReadAt,
            ]);
        }

        $chatRoom->messages()->create([
            'user_id' => $adviser->id,
            'message' => 'Archived chat update',
            'message_type' => 'system',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

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

        $participantRows = DB::table('chat_participants')
            ->where('chat_room_id', $chatRoom->id)
            ->get();

        $this->assertCount(3, $participantRows);
        $participantRows->each(function ($participantRow) use ($oldReadAt) {
            $this->assertNotNull($participantRow->last_read_at);
            $this->assertGreaterThan($oldReadAt->timestamp, strtotime($participantRow->last_read_at));
        });

        $chatRooms = $this
            ->actingAs($leader)
            ->get(route('chat.index'))
            ->assertOk()
            ->viewData('chatRooms');

        $this->assertSame(0, $chatRooms->firstWhere('id', $chatRoom->id)->unread_count);
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

        return [$leader, $adviser, $group, $member];
    }
}
