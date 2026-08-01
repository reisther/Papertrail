<?php

namespace App\Services;

use App\Models\LoginDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginSecurityService
{
    public function details(Request $request): array
    {
        $agent = (string) $request->userAgent();
        $device = match (true) {
            preg_match('/Mobile|Android|iPhone|iPad/i', $agent) === 1 => 'Mobile device',
            str_contains($agent, 'Windows') => 'Windows computer',
            str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS') => 'Mac computer',
            str_contains($agent, 'Linux') => 'Linux computer',
            default => 'Unrecognized device',
        };
        $browser = match (true) {
            preg_match('/Edg\//', $agent) === 1 => 'Microsoft Edge',
            preg_match('/Chrome\//', $agent) === 1 => 'Google Chrome',
            preg_match('/Firefox\//', $agent) === 1 => 'Mozilla Firefox',
            preg_match('/Safari\//', $agent) === 1 => 'Safari',
            default => 'Unrecognized browser',
        };
        $city = $request->header('CF-IPCity') ?: $request->header('X-Vercel-IP-City');
        $country = $request->header('CF-IPCountry') ?: $request->header('X-Vercel-IP-Country');
        $location = collect([$city, $country])->filter()->implode(', ');

        return [
            'date_time' => now()->format('F j, Y g:i A T'),
            'device' => $device,
            'browser' => $browser,
            'location' => $location ?: 'Unavailable',
            'ip_address' => $request->ip(),
            'fingerprint' => hash('sha256', $agent.'|'.($location ?: $request->ip())),
        ];
    }

    public function recordSuccessfulLogin(User $user, Request $request): bool
    {
        $details = $this->details($request);
        $device = LoginDevice::firstOrNew([
            'user_id' => $user->id,
            'fingerprint' => $details['fingerprint'],
        ]);
        $isNew = ! $device->exists;
        $device->fill([
            'ip_address' => $details['ip_address'],
            'device' => $details['device'],
            'browser' => $details['browser'],
            'location' => $details['location'],
            'last_seen_at' => now(),
        ])->save();

        return $isNew;
    }

    public function invalidateSessions(User $user): void
    {
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
