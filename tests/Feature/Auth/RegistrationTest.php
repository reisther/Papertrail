<?php

namespace Tests\Feature\Auth;

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
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'Leader',
            'status' => 'Pending',
        ]);
        $response->assertRedirect(route('registration.success', absolute: false));
    }
}
