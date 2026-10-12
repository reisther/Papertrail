<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_opens_the_login_modal(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect('/');
        $response->assertSessionHas('openLoginModal', true);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_pending_users_are_told_to_wait_for_admin_approval(): void
    {
        $user = User::factory()->create(['status' => 'Pending']);

        $response = $this->from('/')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertGuest();
        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'email' => 'Your account is still awaiting admin approval. Please wait for the confirmation email, or contact support@papertrailpsu.com if you need help.',
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_scheduled_account_login_asks_before_cancelling_deletion(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->addDays(15)]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/');
        $response->assertSessionHas('openDeletionModal', true);
        $this->get('/')->assertSee('scheduled for permanent deletion in 15 days');
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);

        $this->post('/account-deletion/keep')->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_confirming_login_restores_a_scheduled_account(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->addDays(15)]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHas('openDeletionModal', true);

        $this->post('/account-deletion/restore')->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_wrong_password_does_not_offer_account_restoration(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->addDays(15)]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionMissing('account_deletion_confirmation');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_expired_deletion_period_cannot_be_cancelled_by_login(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->subSecond()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionMissing('account_deletion_confirmation');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_confirmation_cannot_restore_an_account_after_its_deadline(): void
    {
        $user = User::factory()->create(['deletion_scheduled_for' => now()->addMinute()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHas('openDeletionModal', true);

        $this->travel(2)->minutes();
        $this->post('/account-deletion/restore')->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_pending_account_can_cancel_deletion_without_bypassing_approval(): void
    {
        $user = User::factory()->create([
            'status' => 'Pending',
            'deletion_scheduled_for' => now()->addDays(15),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHas('openDeletionModal', true);

        $this->post('/account-deletion/restore')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->deletion_scheduled_for);
    }
}
