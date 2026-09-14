<?php

namespace App\Services;

use App\Models\AcademicTopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AcademicRepositoryKnowledgeService
{
    public const REQUIRED_BLOCK_TYPES = ['objective', 'presentation', 'evaluation', 'assignment'];

    public function __construct(
        private AcademicRepositoryQualityService $quality,
        private AcademicRepositorySourcePriorityService $sources,
        private AcademicRepositoryTopicConsolidationService $consolidation,
    ) {
    }

    public function readiness(AcademicTopic $topic): array
    {
        $approved = $this->consolidation->blocks($topic, [
            'objective','presentation','definition','explanation','example','practical','note','evaluation','assignment',
        ]);

        $checks = [
            'Topic mapped' => filled($topic->topic),
            'Sub-topic mapped' => filled($this->consolidation->scalar($topic, 'sub_topic')),
            'Week mapped' => filled($this->consolidation->scalar($topic, 'week_number')),
            'Lesson mapped' => filled($this->consolidation->scalar($topic, 'lesson_number')),
            'Lesson time' => filled($this->consolidation->scalar($topic, 'lesson_time')),
            'Duration' => filled($this->consolidation->scalar($topic, 'duration_minutes')),
            'Average age' => filled($this->consolidation->scalar($topic, 'average_age')),
            'Sex' => filled($this->consolidation->scalar($topic, 'sex')),
            'Entry behaviour' => filled($this->consolidation->scalar($topic, 'entry_behaviour')),
            'Previous knowledge' => filled($this->consolidation->scalar($topic, 'previous_knowledge')),
            'Instructional resources' => filled($this->consolidation->scalar($topic, 'instructional_resources')),
            'Introduction' => filled($this->consolidation->scalar($topic, 'introduction')),
            'Reference' => filled($this->consolidation->scalar($topic, 'reference')),
            'Objectives' => $approved->where('block_type', 'objective')->isNotEmpty(),
            'Presentation steps' => $approved->where('block_type', 'presentation')->isNotEmpty(),
            'Evaluation' => $approved->where('block_type', 'evaluation')->isNotEmpty(),
            'Assignment' => $approved->where('block_type', 'assignment')->isNotEmpty(),
            'Student-note content' => filled($this->consolidation->scalar($topic, 'student_note_summary')) || $approved->whereIn('block_type', ['definition', 'explanation', 'example', 'practical', 'note'])->isNotEmpty(),
        ];

        $complete = collect($checks)->filter()->count();
        $coverageScore = (int) round(($complete / count($checks)) * 100);
        $quality = $this->quality->inspect($topic);
        $combinedScore = (int) round(($coverageScore * 0.55) + ($quality['score'] * 0.45));

        return [
            'score' => $combinedScore,
            'coverage_score' => $coverageScore,
            'quality_score' => $quality['score'],
            'ready' => $coverageScore >= 80
                && $quality['score'] >= 70
                && empty($quality['critical'])
                && $topic->status === 'approved',
            'checks' => $checks,
            'missing' => collect($checks)->filter(fn ($ok) => ! $ok)->keys()->values()->all(),
            'quality' => $quality,
            'sources' => $this->sources->provenance($topic),
            'consolidation' => $this->consolidation->summary($topic),
        ];
    }

    public function lessonPlan(AcademicTopic $topic): array
    {
        $blocks = $this->consolidation->blocks($topic, ['objective','presentation','evaluation','assignment']);
        $readiness = $this->readiness($topic);

        return [
            'class' => $topic->class_label,
            'subject' => $topic->subject_label,
            'week' => $this->consolidation->scalar($topic, 'week_number'),
            'lesson' => $this->consolidation->scalar($topic, 'lesson_number'),
            'topic' => $topic->topic,
            'sub_topic' => $this->consolidation->scalar($topic, 'sub_topic'),
            'time' => $this->consolidation->scalar($topic, 'lesson_time'),
            'duration' => $this->consolidation->scalar($topic, 'duration_minutes'),
            'average_age' => $this->consolidation->scalar($topic, 'average_age'),
            'sex' => $this->consolidation->scalar($topic, 'sex'),
            'entry_behaviour' => $this->consolidation->scalar($topic, 'entry_behaviour'),
            'previous_knowledge' => $this->consolidation->scalar($topic, 'previous_knowledge'),
            'behavioural_objectives' => $this->contents($blocks, 'objective'),
            'instructional_resources' => $this->consolidation->scalar($topic, 'instructional_resources'),
            'introduction' => $this->consolidation->scalar($topic, 'introduction'),
            'presentation' => $blocks->where('block_type', 'presentation')->values()->map(fn ($block, $index) => [
                'title' => $block->title ?: 'Step '.($index + 1),
                'content' => trim($block->content),
            ])->all(),
            'evaluation' => $this->contents($blocks, 'evaluation'),
            'assignment' => $this->contents($blocks, 'assignment'),
            'reference' => $this->consolidation->scalar($topic, 'reference'),
            'quality' => [
                'score' => $readiness['quality_score'],
                'coverage_score' => $readiness['coverage_score'],
                'issues' => $readiness['quality']['issues'],
            ],
            'sources' => $readiness['sources'],
            'consolidation' => $readiness['consolidation'],
        ];
    }

    public function studentNote(AcademicTopic $topic): array
    {
        $blocks = $this->consolidation->blocks($topic, [
            'objective','definition','explanation','example','practical','note','presentation','evaluation','assignment',
        ]);
        $content = $blocks->whereIn('block_type', ['definition', 'explanation', 'example', 'practical', 'note', 'presentation'])
            ->values()
            ->map(fn ($block) => [
                'heading' => $block->title ?: Str::headline($block->block_type),
                'content' => trim($block->content),
            ])->all();
        $readiness = $this->readiness($topic);

        return [
            'class' => $topic->class_label,
            'subject' => $topic->subject_label,
            'term' => $topic->term_label,
            'week' => $this->consolidation->scalar($topic, 'week_number'),
            'topic' => $topic->topic,
            'sub_topic' => $this->consolidation->scalar($topic, 'sub_topic'),
            'objectives' => $this->contents($blocks, 'objective'),
            'summary' => $this->consolidation->scalar($topic, 'student_note_summary'),
            'content' => $content,
            'review_questions' => $this->contents($blocks, 'evaluation'),
            'assignment' => $this->contents($blocks, 'assignment'),
            'reference' => $this->consolidation->scalar($topic, 'reference'),
            'quality' => [
                'score' => $readiness['quality_score'],
                'coverage_score' => $readiness['coverage_score'],
                'issues' => $readiness['quality']['issues'],
            ],
            'sources' => $readiness['sources'],
            'consolidation' => $readiness['consolidation'],
        ];
    }

    private function contents(Collection $blocks, string $type): array
    {
        return $blocks->where('block_type', $type)
            ->pluck('content')
            ->map(fn ($content) => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }
}
