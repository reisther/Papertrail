<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\LoginSecurityService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        Request $request,
        LoginSecurityService $loginSecurity,
        EmailNotificationService $notifications
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = Str::lower(trim($validated['email']));
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        $expiresAt = $reset?->created_at
            ? Carbon::parse($reset->created_at)->addMinutes(config('auth.passwords.users.expire', 60))
            : null;

        if (! $reset || ! $expiresAt || now()->greaterThan($expiresAt)) {
            throw ValidationException::withMessages([
                'code' => 'The password reset code is invalid or expired.',
            ]);
        }

        if (! Hash::check($validated['code'], $reset->token)) {
            $attempts = ((int) ($reset->attempts ?? 0)) + 1;

            if ($attempts >= 3) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                throw ValidationException::withMessages([
                    'code' => 'The OTP is invalid after three incorrect attempts. Request a new OTP.',
                ]);
            }

            DB::table('password_reset_tokens')->where('email', $email)->update(['attempts' => $attempts]);
            throw ValidationException::withMessages([
                'code' => 'The one-time password is incorrect. You have '.(3 - $attempts).' attempt(s) remaining.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'We could not find a PaperTrail account with that email address.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
            'failed_login_attempts' => 0,
            'login_delay_until' => null,
            'locked_until' => null,
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $loginSecurity->invalidateSessions($user);
        $request->session()->forget(['reset_email', 'unlock_user_id', 'unlock_email', 'account_locked']);

        event(new PasswordReset($user));
        $notifications->sendPasswordResetConfirmation($user);

        return redirect()->route('login')->with('status', 'Your password has been reset. Log in again using your new password.');
    }
}
