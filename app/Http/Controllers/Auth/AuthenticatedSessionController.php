<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\LoginSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): RedirectResponse
    {
        return redirect('/')->with('openLoginModal', true);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        LoginSecurityService $loginSecurity,
        EmailNotificationService $notifications
    ): RedirectResponse {
        if (! $request->authenticate()) {
            return redirect('/')->with('openDeletionModal', true);
        }

        $request->session()->regenerate();

        return $this->completeLogin($request, $loginSecurity, $notifications);
    }

    public function restore(
        Request $request,
        LoginSecurityService $loginSecurity,
        EmailNotificationService $notifications
    ): RedirectResponse {
        $confirmation = $request->session()->pull('account_deletion_confirmation');

        if (! is_array($confirmation) || ($confirmation['expires_at'] ?? 0) < now()->timestamp) {
            return $this->restoreFailed();
        }

        $user = DB::transaction(function () use ($confirmation): ?User {
            $user = User::query()->lockForUpdate()->find($confirmation['user_id'] ?? null);

            if (! $user || ! $user->deletion_scheduled_for || ! $user->deletion_scheduled_for->isFuture()) {
                return null;
            }

            $user->forceFill(['deletion_scheduled_for' => null])->save();

            return $user;
        });

        if (! $user) {
            return $this->restoreFailed();
        }

        if ($user->status === 'Pending') {
            return redirect('/')->with('openLoginModal', true)->withErrors([
                'email' => 'Account deletion was cancelled, but this account is still awaiting admin approval.',
            ]);
        }

        Auth::login($user, (bool) ($confirmation['remember'] ?? false));
        $request->session()->regenerate();

        return $this->completeLogin($request, $loginSecurity, $notifications);
    }

    public function keepDeletion(Request $request): RedirectResponse
    {
        $request->session()->forget('account_deletion_confirmation');

        return redirect('/');
    }

    private function restoreFailed(): RedirectResponse
    {
        return redirect('/')->with('openLoginModal', true)->withErrors([
            'email' => 'The account could not be restored. Please sign in again if the 30-day recovery period has not ended.',
        ]);
    }

    private function completeLogin(
        Request $request,
        LoginSecurityService $loginSecurity,
        EmailNotificationService $notifications
    ): RedirectResponse {
        // Update last login time
        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        if ($loginSecurity->recordSuccessfulLogin($user, $request)) {
            $notifications->sendNewLogin(
                $user,
                $loginSecurity->details($request),
                URL::temporarySignedRoute('security.secure', now()->addDay(), ['user' => $user->id])
            );
        }

        // Debug: Log successful login
        \Log::info('User logged in: '.$user->email.' (Role: '.$user->role.')');

        if ($user->role === 'Admin') {
            \Log::info('Redirecting to admin dashboard');

            return redirect(route('admin.dashboard'));
        } elseif ($user->role === 'Teacher') {
            \Log::info('Redirecting to teacher dashboard');

            return redirect(route('teacher.dashboard'));
        } elseif ($user->role === 'Leader') {
            \Log::info('Redirecting to leader dashboard');

            return redirect(route('dashboard'));
        } else {
            \Log::info('Redirecting to student dashboard');

            return redirect(route('dashboard'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
