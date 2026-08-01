<?php

namespace Tests\Feature\Auth;

use App\Mail\PaperTrailNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('status', 'Enter the OTP sent to your email.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => strtolower($user->email),
        ]);

        Mail::assertSent(PaperTrailNotification::class, function (PaperTrailNotification $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->subjectLine === 'PaperTrail: Password reset code'
                && str_contains($mail->emailData['body'], 'Your password reset code is:');
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_code(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $code = '123456';
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'another-device',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Other device',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => strtolower($user->email),
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => strtolower($user->email)]);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-device']);
        Mail::assertSent(PaperTrailNotification::class, fn ($mail) => $mail->hasTo($user->email) && $mail->subjectLine === 'PaperTrail: Password reset complete'
        );
    }

    public function test_otp_is_invalidated_after_three_incorrect_attempts(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert([
            'email' => strtolower($user->email),
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post('/reset-password', [
                'email' => $user->email,
                'code' => '654321',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('code');
        }

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => strtolower($user->email)]);
    }

    public function test_otp_expires_after_ten_minutes(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert([
            'email' => strtolower($user->email),
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(11),
        ]);

        $this->post('/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['code' => 'The password reset code is invalid or expired.']);
    }
}
