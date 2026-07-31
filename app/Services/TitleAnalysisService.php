<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TitleAnalysisService
{
    public function analyze(array $titles): ?string
    {
        if (! config('services.papertrail_ai.enabled')) {
            return null;
        }

        $payload = collect(array_values($titles))
            ->take(5)
            ->mapWithKeys(fn (string $title, int $index) => [
                'title'.($index + 1) => $title,
            ])
            ->all();

        if (count($payload) !== 5) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout(config('services.papertrail_ai.timeout'))
                ->post(config('services.papertrail_ai.url').'/analyze', $payload);
        } catch (ConnectionException $exception) {
            Log::warning('PaperTrail AI service is unavailable; using local title matching.', [
                'exception' => $exception::class,
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('PaperTrail AI service returned an error; using local title matching.', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $analysis = $response->json('analysis');

        return is_string($analysis) && trim($analysis) !== ''
            ? trim($analysis)
            : null;
    }
}
