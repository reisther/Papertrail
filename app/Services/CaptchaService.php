<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaService
{
    public function verify(Request $request, mixed $token): bool
    {
        $token = trim((string) $token);
        $secret = (string) config('services.recaptcha.secret_key');

        if ($token === '') {
            return false;
        }

        if (app()->environment('testing') && config('services.recaptcha.testing_bypass')) {
            return true;
        }

        if ($secret === '') {
            Log::error('Google reCAPTCHA verification is not configured.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.recaptcha.timeout', 5))
                ->post((string) config('services.recaptcha.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            if (! $response->successful() || $response->json('success') !== true) {
                Log::warning('Google reCAPTCHA verification failed.', [
                    'status' => $response->status(),
                    'error_codes' => $response->json('error-codes', []),
                ]);

                return false;
            }

            $expectedHostname = trim((string) config('services.recaptcha.expected_hostname'));

            return $expectedHostname === ''
                || hash_equals($expectedHostname, (string) $response->json('hostname'));
        } catch (\Throwable $exception) {
            Log::warning('Google reCAPTCHA verification request failed.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
