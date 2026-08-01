<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\CaptchaService;
use App\Services\EmailNotificationService;
use App\Services\LoginSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'g-recaptcha-response' => ['nullable', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $email = Str::lower(trim((string) $this->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $attempts = $user?->failed_login_attempts ?? RateLimiter::attempts($this->throttleKey());

        $this->ensureLoginIsAllowed($user, $attempts);

        if ($attempts >= 3) {
            $captcha = app(CaptchaService::class);
            if (! $captcha->verify($this, $this->input('g-recaptcha-response'))) {
                $this->session()->put('login_captcha_required', true);

                throw ValidationException::withMessages([
                    'g-recaptcha-response' => 'Complete the “I’m not a robot” verification before trying again.',
                ]);
            }
        }

        if (! Auth::attempt(['email' => $user?->email ?? $email, 'password' => $this->input('password')], $this->boolean('remember'))) {
            $this->recordFailure($user);
        }

        if (Auth::user()->status === 'Pending') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account is pending admin verification. Please wait for approval.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        $this->session()->forget(['login_captcha_required', 'login_delay_until', 'account_locked', 'unlock_user_id', 'unlock_email']);
        Auth::user()->forceFill([
            'failed_login_attempts' => 0,
            'login_delay_until' => null,
            'locked_until' => null,
        ])->save();
    }

    private function ensureLoginIsAllowed(?User $user, int $attempts): void
    {
        $lockedUntil = $user?->locked_until;
        if ($lockedUntil?->isFuture()) {
            $this->prepareUnlock($user);
            throw ValidationException::withMessages([
                'email' => 'Your account has been temporarily locked because of multiple unsuccessful login attempts. You may try again after 15 minutes or verify your identity to unlock your account now.',
            ]);
        }

        $delayUntil = $user?->login_delay_until;
        $sessionDelay = (int) $this->session()->get('login_delay_until', 0);
        $seconds = $delayUntil?->isFuture()
            ? now()->diffInSeconds($delayUntil)
            : max(0, $sessionDelay - now()->timestamp);

        if ($seconds > 0) {
            $this->session()->put('login_captcha_required', true);
            throw ValidationException::withMessages([
                'email' => "Please wait {$seconds} seconds before trying to log in again.",
            ]);
        }

        if ($attempts >= 3) {
            $this->session()->put('login_captcha_required', true);
        }
    }

    private function recordFailure(?User $user): never
    {
        RateLimiter::hit($this->throttleKey(), 15 * 60);
        $attempts = RateLimiter::attempts($this->throttleKey());

        if ($user) {
            $user = DB::transaction(function () use ($user, &$attempts): User {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $attempts = min(5, $lockedUser->failed_login_attempts + 1);
                $lockedUser->failed_login_attempts = $attempts;

                if ($attempts === 3) {
                    $lockedUser->login_delay_until = now()->addSeconds(30);
                }

                if ($attempts >= 5) {
                    $lockedUser->locked_until = now()->addMinutes(15);
                }

                $lockedUser->save();

                return $lockedUser;
            });
        }

        if ($attempts >= 3) {
            $this->session()->put('login_captcha_required', true);
        }

        if ($attempts === 3) {
            $this->session()->put('login_delay_until', now()->addSeconds(30)->timestamp);

            if ($user) {
                $this->sendSecurityEmail($user, false);
            }
        }

        if ($attempts >= 5 && $user) {
            $this->prepareUnlock($user);
            $this->sendSecurityEmail($user, true);
            throw ValidationException::withMessages([
                'email' => 'Your account has been temporarily locked because of multiple unsuccessful login attempts. You may try again after 15 minutes or verify your identity to unlock your account now.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => 'Invalid email address or password.',
        ]);
    }

    private function prepareUnlock(User $user): void
    {
        $this->session()->put([
            'account_locked' => true,
            'unlock_user_id' => $user->id,
            'unlock_email' => $user->email,
        ]);
    }

    private function sendSecurityEmail(User $user, bool $locked): void
    {
        $details = app(LoginSecurityService::class)->details($this);
        $secureLink = URL::temporarySignedRoute('security.secure', now()->addDay(), ['user' => $user->id]);
        $notifications = app(EmailNotificationService::class);

        if ($locked) {
            $notifications->sendAccountLocked($user, $details, $secureLink);
        } else {
            $notifications->sendFailedLoginWarning($user, $details, $secureLink);
        }
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
