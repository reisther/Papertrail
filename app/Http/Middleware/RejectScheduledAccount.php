<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RejectScheduledAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->deletion_scheduled_for || $request->user()?->status === 'Deleted') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('openLoginModal', true)
                ->withErrors(['email' => 'This account is scheduled for deletion. Sign in again to review and cancel the deletion.']);
        }

        return $next($request);
    }
}
