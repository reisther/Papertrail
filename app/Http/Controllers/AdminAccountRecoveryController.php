<?php

namespace App\Http\Controllers;

use App\Models\AdminAccountRecovery;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\LoginSecurityService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAccountRecoveryController extends Controller
{
    public function create(User $user): View
    {
        $this->authorizeAdministrator();

        return view('admin.account-recovery', compact('user'));
    }

    public function store(Request $request, User $user, EmailNotificationService $notifications): RedirectResponse
    {
        $admin = $this->authorizeAdministrator();
        $validated = $request->validate([
            'new_email' => ['required', 'email', Rule::notIn([Str::lower($user->email)]), Rule::unique('users', 'email')->ignore($user->id)],
            'reason' => ['required', 'string', 'max:2000'],
            'verification_channel' => ['required', Rule::in(['secure_channel', 'in_person'])],
            'identity_verified' => ['accepted'],
            'verification_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $plainToken = Str::random(64);
        $documentPath = null;
        if ($request->hasFile('verification_document')) {
            $documentPath = 'account-recovery/'.Str::uuid().'.enc';
            $encrypted = Crypt::encryptString(base64_encode($request->file('verification_document')->get()));
            Storage::disk('local')->put($documentPath, $encrypted);
        }

        try {
            $recovery = DB::transaction(function () use ($user, $admin, $validated, $plainToken, $documentPath) {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $previousEmail = $lockedUser->email;
                $newEmail = Str::lower(trim($validated['new_email']));

                $recovery = AdminAccountRecovery::create([
                    'admin_id' => $admin->id,
                    'user_id' => $lockedUser->id,
                    'previous_email' => $previousEmail,
                    'new_email' => $newEmail,
                    'verification_channel' => $validated['verification_channel'],
                    'reason' => $validated['reason'],
                    'temporary_document_path' => $documentPath,
                    'document_delete_after' => $documentPath ? now()->addDays(config('security.recovery_document_retention_days', 30)) : null,
                    'reset_token_hash' => hash('sha256', $plainToken),
                    'reset_token_expires_at' => now()->addHour(),
                ]);

                $lockedUser->forceFill([
                    'email' => $newEmail,
                    'failed_login_attempts' => 0,
                    'login_delay_until' => null,
                    'locked_until' => null,
                ])->save();

                return $recovery;
            });
        } catch (\Throwable $exception) {
            if ($documentPath) {
                Storage::disk('local')->delete($documentPath);
            }
            throw $exception;
        }

        $resetLink = route('admin-recovery.reset', ['recovery' => $recovery->id, 'token' => $plainToken]);
        $notifications->sendAdministrativeRecoveryNotices(
            $recovery->previous_email,
            $recovery->new_email,
            $resetLink,
            $admin
        );

        return redirect()->route('admin.all-users')->with(
            'success',
            "Recovery completed for {$user->name}. A single-use reset link was sent to the verified new email."
        );
    }

    public function showReset(AdminAccountRecovery $recovery, string $token): View
    {
        $this->validateRecoveryToken($recovery, $token);

        return view('auth.admin-recovery-reset', compact('recovery', 'token'));
    }

    public function reset(
        Request $request,
        AdminAccountRecovery $recovery,
        string $token,
        LoginSecurityService $loginSecurity,
        EmailNotificationService $notifications
    ): RedirectResponse {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($recovery, $token, $validated, $loginSecurity, $notifications) {
            $lockedRecovery = AdminAccountRecovery::query()->lockForUpdate()->findOrFail($recovery->id);
            $this->validateRecoveryToken($lockedRecovery, $token);
            $user = User::query()->lockForUpdate()->findOrFail($lockedRecovery->user_id);
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
                'failed_login_attempts' => 0,
                'login_delay_until' => null,
                'locked_until' => null,
            ])->save();
            $lockedRecovery->update(['reset_token_used_at' => now()]);
            DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();
            $loginSecurity->invalidateSessions($user);
            event(new PasswordReset($user));
            $notifications->sendPasswordResetConfirmation($user);
        });

        return redirect()->route('login')->with('status', 'Your password has been reset. Log in using the new password.');
    }

    private function authorizeAdministrator(): User
    {
        $user = Auth::user();
        abort_unless($user?->isAdmin(), 403, 'Access denied. Authorized administrators only.');

        return $user;
    }

    private function validateRecoveryToken(AdminAccountRecovery $recovery, string $token): void
    {
        if ($recovery->reset_token_used_at
            || $recovery->reset_token_expires_at->isPast()
            || ! hash_equals($recovery->reset_token_hash, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'token' => 'This administrative recovery link is invalid, expired, or has already been used.',
            ]);
        }
    }
}
