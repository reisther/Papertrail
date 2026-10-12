<?php

namespace Tests\Feature;

use App\Models\AdminAccountRecovery;
use App\Models\AdviserStudent;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'firstname' => 'Test',
                'lastname' => 'User',
                'campus' => 'Main Campus',
                'course' => 'Information Technology',
                'section' => 'A',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_adviser_profile_shows_only_department_and_keeps_it_fixed(): void
    {
        $adviser = User::factory()->create(['role' => 'Teacher']);

        $this->actingAs($adviser)->get('/profile')
            ->assertOk()
            ->assertSee('CCS Department')
            ->assertDontSee('name="course"', false)
            ->assertDontSee('name="section"', false);

        $this->patch('/profile', [
            'firstname' => 'Ada',
            'lastname' => 'Adviser',
            'campus' => 'Main Campus',
            'course' => 'Forged course',
            'section' => 'Forged section',
            'email' => $adviser->email,
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $adviser->refresh();
        $this->assertSame(User::ADVISER_DEPARTMENT, $adviser->course);
        $this->assertNull($adviser->section);
    }

    public function test_admin_changing_user_to_adviser_sets_department(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create([
            'role' => 'Student',
            'course' => 'Computer Science',
            'section' => 'A',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.update-user-role', $user), ['role' => 'Teacher'])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(User::ADVISER_DEPARTMENT, $user->course);
        $this->assertNull($user->section);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'firstname' => 'Test',
                'lastname' => 'User',
                'campus' => 'Main Campus',
                'course' => 'Information Technology',
                'section' => 'A',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_schedule_account_deletion_for_30_days(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        $requestedAt = now();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotNull($user->fresh());
        $this->assertTrue($user->deletion_scheduled_for->equalTo($requestedAt->addDays(30)));
        $response->assertSessionHas('account_deletion_scheduled', true);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
        $this->assertNull($user->deletion_scheduled_for);
    }

    public function test_adviser_must_enter_matching_passwords_and_confirm_again(): void
    {
        $this->travelTo(now()->startOfSecond());
        $adviser = User::factory()->create(['role' => 'Teacher']);

        $this->actingAs($adviser)->get('/profile')
            ->assertOk()
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Are you sure you want to delete your adviser account?')
            ->assertSee('students will keep their original group files and chats');

        $this->actingAs($adviser)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password', 'password_confirmation' => 'different'])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', 'password_confirmation');

        $this->assertNull($adviser->fresh()->deletion_scheduled_for);

        $this->delete('/profile', ['password' => 'password', 'password_confirmation' => 'password'])
            ->assertRedirect('/profile')
            ->assertSessionHas('show_adviser_deletion_final_confirmation', true);

        $this->assertAuthenticatedAs($adviser);
        $this->assertNull($adviser->fresh()->deletion_scheduled_for);

        $this->post(route('profile.confirm-destroy'), ['decision' => 'confirm'])
            ->assertRedirect('/')
            ->assertSessionHas('account_deletion_scheduled', true);

        $this->assertGuest();
        $this->assertTrue($adviser->fresh()->deletion_scheduled_for->equalTo(now()->addDays(30)));
    }

    public function test_adviser_can_cancel_final_confirmation_and_cannot_skip_password_step(): void
    {
        $adviser = User::factory()->create(['role' => 'Teacher']);

        $this->actingAs($adviser)
            ->post(route('profile.confirm-destroy'), ['decision' => 'confirm'])
            ->assertRedirect('/profile');

        $this->delete('/profile', ['password' => 'password', 'password_confirmation' => 'password'])
            ->assertRedirect('/profile');

        $this->post(route('profile.confirm-destroy'), ['decision' => 'cancel'])
            ->assertRedirect('/profile');

        $this->post(route('profile.confirm-destroy'), ['decision' => 'confirm'])
            ->assertRedirect('/profile');

        $this->assertAuthenticatedAs($adviser);
        $this->assertNull($adviser->fresh()->deletion_scheduled_for);
    }

    public function test_deleted_adviser_loses_archive_while_students_keep_original_files_and_chats(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/student.pdf', 'student document');
        Storage::disk('public')->put('chat/student.pdf', 'student chat attachment');
        Storage::disk('public')->put('adviser_schedules/schedule.pdf', 'private adviser schedule');

        $student = User::factory()->create(['role' => 'Leader']);
        $adviser = User::factory()->create([
            'role' => 'Teacher',
            'adviser_schedule_path' => 'adviser_schedules/schedule.pdf',
            'deletion_scheduled_for' => now()->subSecond(),
        ]);
        $project = Project::create([
            'title' => 'Student original',
            'owner_id' => $student->id,
            'adviser_id' => $adviser->id,
        ]);
        $relationship = AdviserStudent::create([
            'student_id' => $student->id,
            'adviser_id' => $adviser->id,
            'status' => 'approved',
            'archived_at' => now()->subDay(),
        ]);
        $document = Document::create([
            'name' => 'Student file',
            'original_name' => 'student.pdf',
            'file_path' => 'documents/student.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 16,
            'project_id' => $project->id,
            'uploaded_by' => $student->id,
        ]);
        $room = ChatRoom::create([
            'name' => 'Student chat',
            'type' => 'project',
            'project_id' => $project->id,
            'created_by' => $student->id,
        ]);
        $room->addParticipant($student, 'admin');
        $room->addParticipant($adviser, 'moderator');
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => $student->id,
            'message' => 'Student attachment',
            'message_type' => 'file',
            'file_path' => 'chat/student.pdf',
            'file_name' => 'student.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 23,
        ]);

        $this->artisan('accounts:purge-deleted')->assertExitCode(0);

        $this->assertSame('Deleted', $adviser->fresh()->status);
        $this->assertNull($relationship->fresh());
        $this->assertNull($project->fresh()->adviser_id);
        $this->assertTrue($project->fresh()->canAccess($student));
        $this->assertNotNull($document->fresh());
        $this->assertNotNull($message->fresh());
        $this->assertDatabaseMissing('chat_participants', ['chat_room_id' => $room->id, 'user_id' => $adviser->id]);
        Storage::disk('public')->assertExists('documents/student.pdf');
        Storage::disk('public')->assertExists('chat/student.pdf');
        Storage::disk('public')->assertMissing('adviser_schedules/schedule.pdf');

        $this->actingAs($student)->get(route('projects.show', $project))->assertOk();
        $this->get(route('chat.rooms.messages', $room))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Student attachment');
    }

    public function test_due_accounts_lose_access_while_adviser_archives_keep_files_and_chats(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('profile-pictures/test.jpg', 'picture');
        Storage::disk('public')->put('documents/test.pdf', 'document');
        Storage::disk('public')->put('chat/test.pdf', 'chat attachment');
        Storage::disk('local')->put('account-recovery/identity.pdf', 'identity');

        $user = User::factory()->create([
            'role' => 'Leader',
            'profile_picture_path' => 'profile-pictures/test.jpg',
            'deletion_scheduled_for' => now()->addDays(30),
        ]);
        $originalEmail = $user->email;
        $admin = User::factory()->create(['role' => 'Admin']);
        $adviser = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create(['title' => 'Test project', 'owner_id' => $user->id, 'adviser_id' => $adviser->id]);
        $document = Document::create([
            'name' => 'Test document',
            'original_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'project_id' => $project->id,
            'uploaded_by' => $user->id,
        ]);
        $relationship = AdviserStudent::create([
            'student_id' => $user->id,
            'adviser_id' => $adviser->id,
            'status' => 'approved',
        ]);
        $room = ChatRoom::create([
            'name' => 'Adviser Chat',
            'type' => 'project',
            'project_id' => $project->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        $room->addParticipant($user, 'admin');
        $room->addParticipant($adviser, 'moderator');
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'message' => 'Remember this file',
            'message_type' => 'file',
            'file_path' => 'chat/test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 15,
        ]);
        AdminAccountRecovery::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'previous_email' => $user->email,
            'new_email' => $user->email,
            'verification_channel' => 'in_person',
            'reason' => 'Test',
            'temporary_document_path' => 'account-recovery/identity.pdf',
            'reset_token_hash' => str_repeat('a', 64),
            'reset_token_expires_at' => now()->addHour(),
        ]);
        $deadline = $user->deletion_scheduled_for->copy();

        $this->travelTo($deadline->copy()->subSecond());
        $this->artisan('accounts:purge-deleted')->assertExitCode(0);
        $this->assertNotNull($user->fresh());
        Storage::disk('public')->assertExists('profile-pictures/test.jpg');

        $this->travelTo($deadline->copy()->addSecond());
        $this->artisan('accounts:purge-deleted')->assertExitCode(0);
        $user->refresh();
        $this->assertSame('Deleted', $user->status);
        $this->assertSame('Deleted User', $user->name);
        $this->assertNotSame($originalEmail, $user->email);
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertNull($user->deletion_scheduled_for);
        $this->assertNull($user->profile_picture_path);
        Storage::disk('public')->assertMissing('profile-pictures/test.jpg');
        Storage::disk('public')->assertExists('documents/test.pdf');
        Storage::disk('public')->assertExists('chat/test.pdf');
        Storage::disk('local')->assertMissing('account-recovery/identity.pdf');
        $this->assertSame('archived', $project->fresh()->status);
        $this->assertNotNull($relationship->fresh()->archived_at);
        $this->assertNotNull($document->fresh());
        $this->assertNotNull($message->fresh());
        $this->assertDatabaseMissing('admin_account_recoveries', ['user_id' => $user->id]);

        $this->post('/login', ['email' => $originalEmail, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($adviser)->get(route('projects.archived'))->assertOk()->assertSee('Test project');
        $this->get(route('chat.archived'))->assertOk()->assertSee('Adviser Chat');
        $this->get(route('chat.rooms.messages', $room))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Remember this file')
            ->assertJsonPath('messages.0.user.name', 'Deleted User')
            ->assertJsonPath('messages.0.is_archived', true);
    }

    public function test_scheduled_account_cannot_use_an_existing_session(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->addDays(30)]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_deleted_member_contributions_remain_in_the_active_adviser_group(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/member.pdf', 'member document');

        $leader = User::factory()->create(['role' => 'Leader']);
        $member = User::factory()->create([
            'role' => 'Student',
            'deletion_scheduled_for' => now()->subSecond(),
        ]);
        $adviser = User::factory()->create(['role' => 'Teacher']);
        $project = Project::create([
            'title' => 'Active group',
            'owner_id' => $leader->id,
            'adviser_id' => $adviser->id,
            'status' => 'active',
        ]);
        $project->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $document = Document::create([
            'name' => 'Member file',
            'original_name' => 'member.pdf',
            'file_path' => 'documents/member.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 15,
            'project_id' => $project->id,
            'uploaded_by' => $member->id,
        ]);
        $room = ChatRoom::create([
            'name' => 'Active adviser chat',
            'type' => 'project',
            'project_id' => $project->id,
            'created_by' => $leader->id,
        ]);
        $room->addParticipant($adviser, 'moderator');
        $room->addParticipant($member);
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => $member->id,
            'message' => 'Member contribution',
            'message_type' => 'text',
        ]);

        $this->artisan('accounts:purge-deleted')->assertExitCode(0);

        $this->assertSame('Deleted', $member->fresh()->status);
        $this->assertSame('active', $project->fresh()->status);
        $this->assertNotNull($document->fresh());
        $this->assertNotNull($message->fresh());
        Storage::disk('public')->assertExists('documents/member.pdf');

        $this->actingAs($adviser)->get(route('projects.show', $project))->assertOk();
        $this->get(route('chat.rooms.messages', $room))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Member contribution')
            ->assertJsonPath('messages.0.user.name', 'Deleted User');
    }

    public function test_admin_deletion_keeps_shared_project_history(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $leader = User::factory()->create(['role' => 'Leader']);
        $project = Project::create(['title' => 'Saved archive', 'owner_id' => $leader->id]);
        $room = ChatRoom::create([
            'name' => 'Saved conversation',
            'type' => 'project',
            'project_id' => $project->id,
            'created_by' => $leader->id,
        ]);
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => $leader->id,
            'message' => 'Past discussion',
            'message_type' => 'text',
        ]);

        $this->actingAs($admin)->delete(route('admin.delete-user', $leader))
            ->assertSessionHas('success');

        $this->assertSame('Deleted', $leader->fresh()->status);
        $this->assertSame('archived', $project->fresh()->status);
        $this->assertNotNull($room->fresh());
        $this->assertNotNull($message->fresh());
    }
}
