<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('public');

        $response = $this->post('/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'campus' => 'Main Campus',
            'course' => 'Computer Science',
            'section' => 'A',
            'id_document_file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            'role' => 'Leader',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'terms' => 'on',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'Leader',
            'status' => 'Pending',
        ]);
        $response->assertRedirect(route('home', absolute: false));
        $response->assertSessionHas('registration_pending.email', 'test@example.com');
    }

    public function test_adviser_registration_uses_ccs_department_without_course_or_section(): void
    {
        Storage::fake('public');

        $this->get('/')
            ->assertOk()
            ->assertSee('CCS Department')
            ->assertSee('x-model="role"', false)
            ->assertSee('x-bind:disabled="role === \'Teacher\'"', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('CCS Department')
            ->assertSee('x-bind:disabled="role === \'Teacher\'"', false);

        $response = $this->post('/register', [
            'firstname' => 'Ada',
            'lastname' => 'Adviser',
            'campus' => 'Main Campus',
            'id_document_file' => UploadedFile::fake()->create('employee-id.pdf', 100, 'application/pdf'),
            'role' => 'Teacher',
            'email' => 'adviser@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'terms' => 'on',
        ]);

        $response->assertRedirect(route('home', absolute: false));
        $adviser = User::where('email', 'adviser@example.com')->firstOrFail();
        $this->assertSame(User::ADVISER_DEPARTMENT, $adviser->course);
        $this->assertNull($adviser->section);
    }

    public function test_adviser_cannot_override_department_or_section_at_registration(): void
    {
        Storage::fake('public');

        $this->post('/register', [
            'firstname' => 'Ada',
            'lastname' => 'Adviser',
            'campus' => 'Main Campus',
            'course' => 'Computer Science',
            'section' => 'A',
            'id_document_file' => UploadedFile::fake()->create('employee-id.pdf', 100, 'application/pdf'),
            'role' => 'Teacher',
            'email' => 'adviser-override@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'terms' => 'on',
        ])->assertRedirect(route('home', absolute: false));

        $adviser = User::where('email', 'adviser-override@example.com')->firstOrFail();
        $this->assertSame(User::ADVISER_DEPARTMENT, $adviser->course);
        $this->assertNull($adviser->section);
    }

    public function test_student_registration_still_requires_course_and_section(): void
    {
        Storage::fake('public');

        $this->post('/register', [
            'firstname' => 'Student',
            'lastname' => 'User',
            'campus' => 'Main Campus',
            'id_document_file' => UploadedFile::fake()->create('student-id.pdf', 100, 'application/pdf'),
            'role' => 'Leader',
            'email' => 'student-no-course@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'terms' => 'on',
        ])->assertSessionHasErrors(['course', 'section'], null, 'registration');

        $this->assertDatabaseMissing('users', ['email' => 'student-no-course@example.com']);
    }

    public function test_registration_requires_each_password_character_type(): void
    {
        Storage::fake('public');

        foreach (['password1!', 'PASSWORD1!', 'Password!', 'Password1'] as $password) {
            $response = $this->post('/register', [
                'firstname' => 'Test',
                'lastname' => 'User',
                'campus' => 'Main Campus',
                'course' => 'Computer Science',
                'section' => 'A',
                'id_document_file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
                'role' => 'Leader',
                'email' => 'test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'terms' => 'on',
            ]);

            $response->assertSessionHasErrors('password', null, 'registration');
            $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        }
    }

    public function test_registration_shows_a_clear_message_when_the_email_is_already_registered(): void
    {
        Storage::fake('public');
        \App\Models\User::factory()->create(['email' => 'registered@example.com']);

        $response = $this->from('/')
            ->post('/register', [
                'firstname' => 'Test',
                'lastname' => 'User',
                'campus' => 'Main Campus',
                'course' => 'Computer Science',
                'section' => 'A',
                'id_document_file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
                'role' => 'Leader',
                'email' => 'registered@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'terms' => 'on',
            ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'email' => 'This email address already has a PaperTrail account. Please sign in instead or use a different email address.',
        ], null, 'registration');
    }
}
