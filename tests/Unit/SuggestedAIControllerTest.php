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

    public function test_mixed_ai_terms_do_not_outweigh_four_iot_titles(): void
    {
        $controller = new SuggestedAIController();

        $titles = [
            'Smart Classroom Monitoring System Using IoT',
            'IoT-Based Flood Monitoring and Early Warning System',
            'Smart Parking Management System Using IoT',
            'IoT-Based Smart Waste Bin Monitoring System',
            'AI-Powered Student Academic Performance Prediction and Recommendation System',
        ];

        $titleScores = $this->invokePrivate($controller, 'scoreTitleExpertise', [$titles]);
        $totalTitleScore = array_sum($titleScores);

        $iotAdviserScore = $this->scoreForField($controller, 'iot', $titleScores, $totalTitleScore);
        $aiMlAdviserScore = $this->scoreForFields($controller, ['machine_learning', 'ai_integration'], $titleScores, $totalTitleScore);

        $this->assertSame(80, $iotAdviserScore);
        $this->assertSame(20, $aiMlAdviserScore);
        $this->assertGreaterThan($aiMlAdviserScore, $iotAdviserScore);
    }

    private function scoreForField(SuggestedAIController $controller, string $field, array $titleScores, float $totalTitleScore): int
    {
        return $this->scoreForFields($controller, [$field], $titleScores, $totalTitleScore);
    }

    private function scoreForFields(SuggestedAIController $controller, array $fields, array $titleScores, float $totalTitleScore): int
    {
        [$score] = $this->invokePrivate($controller, 'scoreAdviserMatch', [
            (object) array_merge(array_fill_keys($fields, true), [
                'custom_expertise' => [],
            ]),
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
