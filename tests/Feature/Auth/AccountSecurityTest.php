<?php

namespace Tests\Feature\Auth;

use App\Mail\PaperTrailNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_two_failures_use_a_non_enumerating_message(): void
    {
        $user = User::factory()->create();

        foreach ([$user->email, 'missing@example.test'] as $email) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $this->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                    ->assertSessionHasErrors(['email' => 'Invalid email address or password.']);
            }
        }
    }

    public function test_third_failure_adds_delay_and_captcha_then_fifth_locks_without_ending_sessions(): void
    {
        Mail::fake();
        config(['services.recaptcha.site_key' => 'test-site-key']);
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'existing-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Existing browser',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

            if ($attempt < 3) {
                $response->assertSessionMissing('login_captcha_required');
                $this->get('/')->assertDontSee('class="g-recaptcha"', false);
            }
        }

        $user->refresh();
        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertTrue($user->login_delay_until->isFuture());
        $this->assertTrue((bool) session('login_captcha_required'));
        $this->get('/')->assertSee('class="g-recaptcha"', false);
        Mail::assertSent(PaperTrailNotification::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->subjectLine === 'PaperTrail: Unsuccessful login attempts detected'
        );

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'g-recaptcha-response' => 'test-token',
        ])->assertSessionHasErrors('email');
        $this->assertSame(3, $user->fresh()->failed_login_attempts);

        $this->travel(31)->seconds();
        foreach ([4, 5] as $attempt) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'g-recaptcha-response' => 'test-token',
            ]);
        }

        $user->refresh();
        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertTrue($user->locked_until->isFuture());
        $this->assertTrue((bool) session('account_locked'));
        $this->assertDatabaseHas('sessions', ['id' => 'existing-session', 'user_id' => $user->id]);
        Mail::assertSent(PaperTrailNotification::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->subjectLine === 'PaperTrail: Account temporarily locked'
        );
    }

    public function test_unlock_requires_captcha_and_sends_a_single_use_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create(['locked_until' => now()->addMinutes(15)]);
        $secureLink = URL::temporarySignedRoute('security.secure', now()->addHour(), ['user' => $user->id]);

        $this->get($secureLink)->assertRedirect(route('account.unlock'));
        $this->get('/unlock-account')->assertOk();

        $this->post('/unlock-account', ['g-recaptcha-response' => 'test-token'])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('status', 'We sent a six-digit one-time password to your registered email address.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => strtolower($user->email),
            'attempts' => 0,
            'purpose' => 'account_unlock',
        ]);
    }

    public function test_new_device_login_is_recorded_and_notified(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->withHeader('User-Agent', 'Mozilla/5.0 Windows Chrome/120.0')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('login_devices', [
            'user_id' => $user->id,
            'device' => 'Windows computer',
            'browser' => 'Google Chrome',
        ]);
        Mail::assertSent(PaperTrailNotification::class, fn ($mail) => $mail->hasTo($user->email) && $mail->subjectLine === 'PaperTrail: New Login to Your Account'
        );
    }
}
