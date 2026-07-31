<?php

namespace App\Http\Controllers;

use App\Models\TitleSubmission;
use App\Models\User;
use App\Services\TitleAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuggestedAIController extends Controller
{
    private const TITLE_SCORE_WEIGHT = 60;

    public function index(Request $request, TitleAnalysisService $titleAnalysisService)
    {
        if (!auth()->user()->canLeadGroup()) {
            abort(403, 'Only group leaders can analyze titles and request advisers.');
        }

        $activeRequest = auth()->user()
            ->adviserRequests()
            ->currentRequest()
            ->first();

        if ($activeRequest) {
            return redirect()
                ->route('advisers.title-submission')
                ->withErrors(['title1' => 'You already have an active adviser request. You can submit new titles after the request is rejected.']);
        }

        $validated = $request->validate([
            'title1' => 'required|string|max:255',
            'title2' => 'required|string|max:255',
            'title3' => 'required|string|max:255',
            'title4' => 'required|string|max:255',
            'title5' => 'required|string|max:255',
        ]);

        $submission = TitleSubmission::updateOrCreate(
            ['student_id' => auth()->id()],
            $validated
        );

        $titles = array_values($submission->only(['title1', 'title2', 'title3', 'title4', 'title5']));
        $analysis = $titleAnalysisService->analyze($titles);
        $scoringInputs = $analysis ? [...$titles, $analysis] : $titles;
        $normalizedTitles = $this->normalizeText(implode(' ', $scoringInputs));
        $titleExpertiseScores = $this->scoreTitleExpertise($scoringInputs);
        $totalTitleScore = array_sum($titleExpertiseScores);

        $advisers = User::where('role', 'Teacher')
            ->where('status', 'Verified')
            ->with('expertise')
            ->get();

        $recommendedAdvisers = $advisers->map(function ($adviser) use ($normalizedTitles, $titleExpertiseScores, $totalTitleScore) {
            [$score, $matched] = $this->scoreAdviserMatch(
                $adviser->expertise,
                $normalizedTitles,
                $titleExpertiseScores,
                $totalTitleScore
            );

            $adviser->match_score = $score;
            $adviser->score = $score;
            $adviser->matched_expertise = $matched;
            $adviser->reason = match (count($matched)) {
                0 => 'No close expertise match found',
                1 => "Closest match: {$matched[0]}",
                default => 'Closest matches: ' . implode(', ', $matched),
            };

            return $adviser;
        })
        ->filter(fn ($adviser) => $adviser->match_score >= 35)
        ->sortByDesc('score')
        ->take(6)
        ->values();

        $currentRequests = auth()->user()
            ->adviserRequests()
            ->currentRequest()
            ->get();

        return view('advisers.suggestedAI', [
            'recommendedAdvisers' => $recommendedAdvisers,
            'advisers' => $advisers,
            'currentRequests' => $currentRequests,
        ]);
    }

    private function normalizeText(string $text): string
    {
        return ' ' . preg_replace('/\s+/', ' ', Str::lower($text)) . ' ';
    }

    private function expertiseTerms(): array
    {
        return [
            'Machine Learning' => [
                'field' => 'machine_learning',
                'terms' => [
                    'machine learning' => 35,
                    'deep learning' => 35,
                    'neural network' => 30,
                    'recommendation' => 28,
                    'predictive' => 26,
                    'prediction' => 24,
                    'classification' => 24,
                    'computer vision' => 24,
                    'natural language processing' => 24,
                    'nlp' => 22,
                ],
            ],
            'AI Integration' => [
                'field' => 'ai_integration',
                'terms' => [
                    'artificial intelligence' => 35,
                    'ai' => 32,
                    'chatbot' => 28,
                    'virtual assistant' => 26,
                    'automation' => 22,
                    'gemini' => 22,
                    'openai' => 22,
                    'expert system' => 22,
                ],
            ],
            'Cybersecurity' => [
                'field' => 'cybersecurity',
                'terms' => [
                    'cybersecurity' => 35,
                    'cyber security' => 35,
                    'threat detection' => 28,
                    'intrusion' => 26,
                    'encryption' => 24,
                    'authentication' => 22,
                    'privacy' => 20,
                    'malware' => 20,
                ],
            ],
            'IoT' => [
                'field' => 'iot',
                'terms' => [
                    'internet of things' => 35,
                    'iot' => 35,
                    'sensor' => 26,
                    'arduino' => 24,
                    'raspberry pi' => 24,
                    'embedded' => 22,
                    'smart device' => 20,
                ],
            ],
            'Cloud Computing' => [
                'field' => 'cloud_computing',
                'terms' => [
                    'cloud computing' => 35,
                    'cloud' => 26,
                    'aws' => 24,
                    'azure' => 24,
                    'serverless' => 24,
                    'deployment' => 18,
                    'hosting' => 18,
                ],
            ],
            'Data Analytics' => [
                'field' => 'data_analytics',
                'terms' => [
                    'data analytics' => 35,
                    'analytics' => 28,
                    'data mining' => 28,
                    'dashboard' => 22,
                    'visualization' => 22,
                    'reporting' => 18,
                    'business intelligence' => 18,
                ],
            ],
            'Web Development' => [
                'field' => 'web_development',
                'terms' => [
                    'web-based' => 32,
                    'web based' => 32,
                    'website' => 30,
                    'web application' => 28,
                    'laravel' => 26,
                    'react' => 24,
                    'portal' => 18,
                    'online platform' => 18,
                ],
            ],
            'Mobile Development' => [
                'field' => 'mobile_development',
                'terms' => [
                    'mobile application' => 35,
                    'mobile app' => 35,
                    'android' => 30,
                    'ios' => 30,
                    'flutter' => 28,
                    'react native' => 26,
                ],
            ],
            'Database Systems' => [
                'field' => 'database_systems',
                'terms' => [
                    'database' => 32,
                    'database system' => 35,
                    'sql' => 26,
                    'mysql' => 26,
                    'inventory' => 20,
                    'records management' => 20,
                    'information system' => 18,
                ],
            ],
            'Networking' => [
                'field' => 'networking',
                'terms' => [
                    'networking' => 35,
                    'network' => 28,
                    'lan' => 24,
                    'wireless' => 22,
                    'connectivity' => 20,
                    'routing' => 20,
                ],
            ],
        ];
    }

    private function scoreTitleExpertise(array $titles): array
    {
        $scores = [];

        foreach ($titles as $title) {
            $normalizedTitle = $this->normalizeText($title);
            $titleScores = [];

            foreach ($this->expertiseTerms() as $name => $config) {
                $titleScore = collect($config['terms'])
                    ->filter(fn (int $weight, string $term) => $this->containsTerm($normalizedTitle, $term))
                    ->sum();

                if ($titleScore > 0) {
                    $titleScores[$name] = $titleScore;
                }
            }

            $totalTitleScore = array_sum($titleScores);

            if ($totalTitleScore === 0) {
                continue;
            }

            foreach ($titleScores as $name => $titleScore) {
                $scores[$name] = ($scores[$name] ?? 0) + (($titleScore / $totalTitleScore) * self::TITLE_SCORE_WEIGHT);
            }
        }

        return collect($scores)
            ->filter()
            ->all();
    }

    private function scoreAdviserMatch($expertise, string $normalizedTitles, array $titleExpertiseScores, float $totalTitleScore): array
    {
        if (! $expertise) {
            return [0, []];
        }

        $matched = [];
        $matchedScore = 0;

        foreach ($this->expertiseTerms() as $name => $config) {
            if (! ($expertise->{$config['field']} ?? false) || ! isset($titleExpertiseScores[$name])) {
                continue;
            }

            $matched[] = $name;
            $matchedScore += $titleExpertiseScores[$name];
        }

        $score = $totalTitleScore > 0
            ? (int) round(($matchedScore / $totalTitleScore) * 100)
            : 0;

        foreach ($expertise->custom_expertise ?? [] as $customExpertise) {
            $customExpertise = trim($customExpertise);

            if ($customExpertise === '' || ! $this->customExpertiseMatches($normalizedTitles, $customExpertise)) {
                continue;
            }

            $matched[] = $customExpertise;
            $score = max($score, $totalTitleScore > 0 ? min($score + 15, 100) : 75);
        }

        return [min($score, 100), array_values(array_unique($matched))];
    }

    private function customExpertiseMatches(string $normalizedTitles, string $customExpertise): bool
    {
        $normalizedCustom = $this->normalizeText($customExpertise);

        if (trim($normalizedCustom) !== '' && str_contains($normalizedTitles, $normalizedCustom)) {
            return true;
        }

        return collect(preg_split('/\s+/', Str::lower($customExpertise)))
            ->map(fn ($term) => trim($term, " ,.;:/\\|()[]{}"))
            ->filter(fn ($term) => mb_strlen($term) > 3)
            ->contains(fn ($term) => $this->containsTerm($normalizedTitles, $term));
    }

    private function containsTerm(string $normalizedText, string $term): bool
    {
        return preg_match('/(?<![a-z0-9])' . preg_quote(Str::lower($term), '/') . '(?![a-z0-9])/', $normalizedText) === 1;
    }
}
