<?php

namespace Tests\Feature\Auth;

use App\Mail\PaperTrailNotification;
use App\Models\AdminAccountRecovery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdministrativeRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_can_open_account_recovery(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.account-recovery.create', $target))
            ->assertForbidden();
    }

    public function test_admin_recovery_is_audited_and_uses_a_single_use_reset_link(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create([
            'email' => 'previous@example.test',
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->actingAs($admin)->post(route('admin.account-recovery.store', $user), [
            'new_email' => 'verified-new@example.test',
            'verification_channel' => 'in_person',
            'reason' => 'Identity matched the current institutional record.',
            'identity_verified' => '1',
        ])->assertRedirect(route('admin.all-users'));

        $recovery = AdminAccountRecovery::firstOrFail();
        $this->assertSame($admin->id, $recovery->admin_id);
        $this->assertSame($user->id, $recovery->user_id);
        $this->assertSame('previous@example.test', $recovery->previous_email);
        $this->assertSame('verified-new@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->locked_until);

        $resetMail = Mail::sent(PaperTrailNotification::class)
            ->first(fn ($mail) => $mail->hasTo('verified-new@example.test'));
        preg_match('~https?://[^\s]+/administrative-recovery/\d+/[^\s]+~', $resetMail->emailData['body'], $matches);
        $resetLink = $matches[0];

        $this->get($resetLink)->assertOk();
        $this->post($resetLink, [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertNotNull($recovery->fresh()->reset_token_used_at);
        $this->get($resetLink)->assertSessionHasErrors('token');
    }
}
