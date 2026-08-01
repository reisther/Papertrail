<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\EmailNotificationService;
use App\Services\LoginSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

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
        $request->authenticate();

        $request->session()->regenerate();

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
