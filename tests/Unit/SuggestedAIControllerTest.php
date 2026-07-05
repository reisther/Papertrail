<?php

namespace Tests\Unit;

use App\Http\Controllers\SuggestedAIController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SuggestedAIControllerTest extends TestCase
{
    public function test_majority_titles_are_weighted_for_every_expertise(): void
    {
        $controller = new SuggestedAIController();
        $expertiseTerms = $this->invokePrivate($controller, 'expertiseTerms');
        $expertiseNames = array_keys($expertiseTerms);

        foreach ($expertiseTerms as $targetName => $targetConfig) {
            $minorityName = collect($expertiseNames)->first(fn ($name) => $name !== $targetName);
            $targetTerm = array_key_first($targetConfig['terms']);
            $minorityTerm = array_key_first($expertiseTerms[$minorityName]['terms']);

            $titles = [
                "Capstone using {$targetTerm}",
                "Research project for {$targetTerm}",
                "Student system with {$targetTerm}",
                "Smart platform powered by {$targetTerm}",
                "Prototype using {$minorityTerm}",
            ];

            $titleScores = $this->invokePrivate($controller, 'scoreTitleExpertise', [$titles]);
            $totalTitleScore = array_sum($titleScores);

            $targetAdviserScore = $this->scoreForField($controller, $targetConfig['field'], $titleScores, $totalTitleScore);
            $minorityAdviserScore = $this->scoreForField($controller, $expertiseTerms[$minorityName]['field'], $titleScores, $totalTitleScore);

            $this->assertGreaterThan(
                $titleScores[$minorityName] ?? 0,
                $titleScores[$targetName] ?? 0,
                "{$targetName} should score higher when it appears in four out of five titles."
            );

            $this->assertGreaterThan(
                $minorityAdviserScore,
                $targetAdviserScore,
                "{$targetName} adviser should rank higher than a one-title {$minorityName} adviser."
            );
        }
    }

    private function scoreForField(SuggestedAIController $controller, string $field, array $titleScores, int $totalTitleScore): int
    {
        [$score] = $this->invokePrivate($controller, 'scoreAdviserMatch', [
            (object) [
                $field => true,
                'custom_expertise' => [],
            ],
            ' ',
            $titleScores,
            $totalTitleScore,
        ]);

        return $score;
    }

    private function invokePrivate(SuggestedAIController $controller, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $arguments);
    }
}
