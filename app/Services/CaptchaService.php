<?php

namespace App\Services;

use Illuminate\Http\Request;

class CaptchaService
{
    public function issue(Request $request, string $context): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $request->session()->put("captcha.{$context}", [
            'answer' => (string) ($left + $right),
            'question' => "{$left} + {$right}",
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return "{$left} + {$right}";
    }

    public function question(Request $request, string $context): string
    {
        $challenge = $request->session()->get("captcha.{$context}");

        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return $this->issue($request, $context);
        }

        return $challenge['question'];
    }

    public function verify(Request $request, string $context, mixed $answer): bool
    {
        $challenge = $request->session()->pull("captcha.{$context}");

        return is_array($challenge)
            && ($challenge['expires_at'] ?? 0) >= now()->timestamp
            && hash_equals((string) ($challenge['answer'] ?? ''), trim((string) $answer));
    }
}
