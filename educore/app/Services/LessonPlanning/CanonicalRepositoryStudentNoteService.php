<?php

namespace App\Services\LessonPlanning;

use App\Models\LessonPlan;
use App\Services\Curriculum\CurriculumRetrievalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CanonicalRepositoryStudentNoteService
{
    public function __construct(private readonly CurriculumRetrievalService $retrieval) {}

    /**
     * Build a learner-ready student note directly from the indexed Academic
     * Repository. No generative AI is used on this path: factual content comes
     * from canonical repository fragments and the lesson plan only supplies
     * structure (topic, evaluation, assignment and reading direction).
     */
    public function build(LessonPlan $plan, string $depth = 'standard'): ?array
    {
        $limit = match ($depth) {
            'concise' => 12,
            'detailed' => 32,
            default => 22,
        };

        $fragments = $this->retrieval->forLessonPlan($plan, $limit)
            ->filter(fn ($fragment) => filled($fragment->content))
            ->values();

        if ($fragments->isEmpty()) {
            return null;
        }

        $context = $this->retrieval->compactContext($fragments);
        $sections = $this->sections($fragments, $depth);
        if ($sections->isEmpty()) {
            return null;
        }

        $subTopics = collect(preg_split('/[,;\n]+/u', (string) $plan->subtopic) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->merge($fragments->pluck('subtopic')->filter())
            ->merge($fragments->pluck('topic')->filter(fn ($topic) => mb_strtolower(trim((string) $topic)) !== mb_strtolower(trim((string) $plan->topic))))
            ->unique(fn ($value) => mb_strtolower((string) $value))
            ->take(8)
            ->values();

        if ($subTopics->isEmpty()) {
            $subTopics = collect([$plan->topic]);
        }

        return [
            'week' => (string) ($plan->week_number ?? ''),
            'lesson' => trim((string) ($plan->lesson_number ?: '1')),
            'topic' => trim((string) $plan->topic),
            'sub_topics' => $subTopics->all(),
            'sections' => $sections->all(),
            'evaluation' => $this->evaluation($plan, $sections),
            'assignment' => $this->assignment($plan),
            'reading_assignment' => 'Review the canonical Academic Repository material used for this lesson and revise the key definitions, explanations, examples and processes under '.trim((string) $plan->topic).'.',
            'source_trace' => $context,
            'generation' => [
                'mode' => 'canonical_repository',
                'ai_used' => false,
                'source_count' => collect($context)->pluck('source_id')->filter()->unique()->count(),
                'fragment_count' => count($context),
            ],
        ];
    }

    private function sections(Collection $fragments, string $depth): Collection
    {
        $maxWordsPerBlock = match ($depth) {
            'concise' => 180,
            'detailed' => 420,
            default => 280,
        };

        return $fragments
            ->groupBy(function ($fragment) {
                return trim((string) ($fragment->subtopic ?: $fragment->topic ?: 'Lesson Content'));
            })
            ->map(function (Collection $items, string $heading) use ($maxWordsPerBlock) {
                $blocks = $items
                    ->flatMap(function ($fragment) {
                        $content = trim((string) $fragment->content);
                        $paragraphs = preg_split('/(?:\r?\n){2,}/u', $content) ?: [$content];

                        return collect($paragraphs)
                            ->map(fn ($paragraph) => trim((string) $paragraph))
                            ->filter();
                    })
                    ->unique(fn ($paragraph) => mb_strtolower(preg_replace('/\s+/u', ' ', $paragraph)))
                    ->map(fn ($paragraph) => $this->limitWords($paragraph, $maxWordsPerBlock))
                    ->filter()
                    ->values()
                    ->map(fn ($paragraph) => ['type' => 'paragraph', 'content' => $paragraph])
                    ->all();

                return [
                    'heading' => $heading !== '' ? $heading : 'Lesson Content',
                    'subheading' => null,
                    'content_blocks' => $blocks,
                ];
            })
            ->filter(fn ($section) => ! empty($section['content_blocks']))
            ->values();
    }

    private function evaluation(LessonPlan $plan, Collection $sections): array
    {
        $existing = collect(preg_split('/\r?\n/u', (string) $plan->evaluation) ?: [])
            ->map(fn ($item) => trim((string) preg_replace('/^\s*(?:\d+|[A-Za-z])[.)]\s*/u', '', $item)))
            ->filter(fn ($item) => mb_strlen($item) >= 8)
            ->values();

        if ($existing->count() >= 3) {
            return $existing->take(8)->all();
        }

        $generated = $sections->pluck('heading')->filter()->unique()
            ->map(fn ($heading) => 'Explain the important ideas presented under '.$heading.'.')
            ->take(max(0, 5 - $existing->count()));

        return $existing->merge($generated)
            ->push('State two important facts you learned about '.trim((string) $plan->topic).'.')
            ->filter(fn ($item) => mb_strlen($item) >= 8)
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function assignment(LessonPlan $plan): string
    {
        $assignment = trim((string) $plan->assignment);
        if (mb_strlen($assignment) >= 8) {
            return $assignment;
        }

        return 'Write a structured summary of '.trim((string) $plan->topic).' using the definitions, explanations and examples in your lesson note.';
    }

    private function limitWords(string $text, int $limit): string
    {
        $normalised = trim(preg_replace('/[\t ]+/u', ' ', $text));
        $words = preg_split('/\s+/u', $normalised) ?: [];
        if (count($words) <= $limit) {
            return $normalised;
        }

        return implode(' ', array_slice($words, 0, $limit)).'…';
    }
}
