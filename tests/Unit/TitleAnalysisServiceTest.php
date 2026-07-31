<?php

namespace Tests\Unit;

use App\Services\TitleAnalysisService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TitleAnalysisServiceTest extends TestCase
{
    private array $titles = [
        'AI Adviser Recommendation System',
        'Machine Learning Student Predictor',
        'Smart Attendance Using AI',
        'IoT Smart Classroom',
        'Cloud-Based Student Portal',
    ];

    public function test_it_returns_fastapi_analysis(): void
    {
        config()->set('services.papertrail_ai', [
            'enabled' => true,
            'url' => 'http://127.0.0.1:8001',
            'timeout' => 35,
        ]);

        Http::fake([
            'http://127.0.0.1:8001/analyze' => Http::response([
                'analysis' => 'Expertise: AI Integration. Keywords: recommendation system.',
            ]),
        ]);

        $analysis = app(TitleAnalysisService::class)->analyze($this->titles);

        $this->assertSame(
            'Expertise: AI Integration. Keywords: recommendation system.',
            $analysis
        );
        Http::assertSent(fn ($request) => $request['title1'] === $this->titles[0]
            && $request['title5'] === $this->titles[4]);
    }

    public function test_it_falls_back_when_fastapi_returns_an_error(): void
    {
        config()->set('services.papertrail_ai', [
            'enabled' => true,
            'url' => 'http://127.0.0.1:8001',
            'timeout' => 35,
        ]);

        Http::fake([
            'http://127.0.0.1:8001/analyze' => Http::response([
                'detail' => 'Provider unavailable.',
            ], 502),
        ]);

        $this->assertNull(app(TitleAnalysisService::class)->analyze($this->titles));
    }

    public function test_it_skips_fastapi_when_disabled(): void
    {
        config()->set('services.papertrail_ai.enabled', false);

        $this->assertNull(app(TitleAnalysisService::class)->analyze($this->titles));
        Http::assertNothingSent();
    }
}
