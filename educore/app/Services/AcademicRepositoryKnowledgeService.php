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
    ) {
    }

    public function readiness(AcademicTopic $topic): array
    {
        $topic->loadMissing('blocks');
        $approved = $topic->blocks->where('is_approved', true);

        $checks = [
            'Topic mapped' => filled($topic->topic),
            'Sub-topic mapped' => filled($topic->sub_topic),
            'Week mapped' => filled($topic->week_number),
            'Lesson mapped' => filled($topic->lesson_number),
            'Lesson time' => filled($topic->lesson_time),
            'Duration' => filled($topic->duration_minutes),
            'Average age' => filled($topic->average_age),
            'Sex' => filled($topic->sex),
            'Entry behaviour' => filled($topic->entry_behaviour),
            'Previous knowledge' => filled($topic->previous_knowledge),
            'Instructional resources' => filled($topic->instructional_resources),
            'Introduction' => filled($topic->introduction),
            'Reference' => filled($topic->reference),
            'Objectives' => $approved->where('block_type', 'objective')->isNotEmpty(),
            'Presentation steps' => $approved->where('block_type', 'presentation')->isNotEmpty(),
            'Evaluation' => $approved->where('block_type', 'evaluation')->isNotEmpty(),
            'Assignment' => $approved->where('block_type', 'assignment')->isNotEmpty(),
            'Student-note content' => filled($topic->student_note_summary) || $approved->whereIn('block_type', ['definition', 'explanation', 'example', 'practical', 'note'])->isNotEmpty(),
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
        ];
    }

    public function lessonPlan(AcademicTopic $topic): array
    {
        $topic->loadMissing('blocks');
        $approved = $topic->blocks->where('is_approved', true);
        $readiness = $this->readiness($topic);

        return [
            'class' => $topic->class_label,
            'subject' => $topic->subject_label,
            'week' => $topic->week_number,
            'lesson' => $topic->lesson_number,
            'topic' => $topic->topic,
            'sub_topic' => $topic->sub_topic,
            'time' => $topic->lesson_time,
            'duration' => $topic->duration_minutes,
            'average_age' => $topic->average_age,
            'sex' => $topic->sex,
            'entry_behaviour' => $topic->entry_behaviour,
            'previous_knowledge' => $topic->previous_knowledge,
            'behavioural_objectives' => $this->contents($approved, 'objective'),
            'instructional_resources' => $topic->instructional_resources,
            'introduction' => $topic->introduction,
            'presentation' => $approved->where('block_type', 'presentation')->sortBy('sequence')->map(fn ($block) => [
                'title' => $block->title ?: 'Step '.$block->sequence,
                'content' => trim($block->content),
            ])->values()->all(),
            'evaluation' => $this->contents($approved, 'evaluation'),
            'assignment' => $this->contents($approved, 'assignment'),
            'reference' => $topic->reference,
            'quality' => [
                'score' => $readiness['quality_score'],
                'coverage_score' => $readiness['coverage_score'],
                'issues' => $readiness['quality']['issues'],
            ],
            'sources' => $readiness['sources'],
        ];
    }

    public function studentNote(AcademicTopic $topic): array
    {
        $topic->loadMissing('blocks');
        $approved = $topic->blocks->where('is_approved', true);
        $content = $approved->whereIn('block_type', ['definition', 'explanation', 'example', 'practical', 'note', 'presentation'])
            ->sortBy('sequence')
            ->map(fn ($block) => [
                'heading' => $block->title ?: Str::headline($block->block_type),
                'content' => trim($block->content),
            ])->values()->all();
        $readiness = $this->readiness($topic);

        return [
            'class' => $topic->class_label,
            'subject' => $topic->subject_label,
            'term' => $topic->term_label,
            'week' => $topic->week_number,
            'topic' => $topic->topic,
            'sub_topic' => $topic->sub_topic,
            'objectives' => $this->contents($approved, 'objective'),
            'summary' => $topic->student_note_summary,
            'content' => $content,
            'review_questions' => $this->contents($approved, 'evaluation'),
            'assignment' => $this->contents($approved, 'assignment'),
            'reference' => $topic->reference,
            'quality' => [
                'score' => $readiness['quality_score'],
                'coverage_score' => $readiness['coverage_score'],
                'issues' => $readiness['quality']['issues'],
            ],
            'sources' => $readiness['sources'],
        ];
    }

    private function contents(Collection $blocks, string $type): array
    {
        return $blocks->where('block_type', $type)
            ->sortBy('sequence')
            ->pluck('content')
            ->map(fn ($content) => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }
}
