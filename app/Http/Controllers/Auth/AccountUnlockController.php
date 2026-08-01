<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountUnlockController extends Controller
{
    public function secure(Request $request, User $user): RedirectResponse
    {
        $request->session()->put([
            'unlock_user_id' => $user->id,
            'unlock_email' => $user->email,
        ]);

        return redirect()->route('account.unlock');
    }

    public function create(Request $request, CaptchaService $captcha): View|RedirectResponse
    {
        if (! $request->session()->has('unlock_user_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Start identity verification from the account-lock notice or a secure account link.',
            ]);
        }

        return view('auth.unlock-account', [
            'captchaQuestion' => $captcha->question($request, 'unlock'),
        ]);
    }

    public function store(
        Request $request,
        CaptchaService $captcha,
        EmailNotificationService $notifications
    ): RedirectResponse {
        $request->validate(['captcha' => ['required', 'string']]);

        if (! $captcha->verify($request, 'unlock', $request->input('captcha'))) {
            $captcha->issue($request, 'unlock');

            return back()->withErrors(['captcha' => 'The CAPTCHA answer is incorrect.']);
        }

        $user = User::find($request->session()->get('unlock_user_id'));
        if (! $user) {
            $request->session()->forget(['unlock_user_id', 'unlock_email']);

            return redirect()->route('login');
        }

        $code = (string) random_int(100000, 999999);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => strtolower($user->email)],
            [
                'token' => Hash::make($code),
                'attempts' => 0,
                'purpose' => 'account_unlock',
                'created_at' => now(),
            ]
        );

        if (! $notifications->sendPasswordResetCode($user, $code)) {
            return back()->withErrors([
                'captcha' => 'We could not send the OTP email right now. Please try again later.',
            ]);
        }

        $request->session()->put('reset_email', $user->email);

        return redirect()->route('password.reset')->with(
            'status',
            'We sent a six-digit one-time password to your registered email address.'
        );
    }
}
