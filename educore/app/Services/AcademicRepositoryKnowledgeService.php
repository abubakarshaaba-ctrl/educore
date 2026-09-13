<?php

namespace App\Services;

use App\Models\AcademicTopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AcademicRepositoryKnowledgeService
{
    public const REQUIRED_BLOCK_TYPES = [
        'objective',
        'presentation',
        'evaluation',
        'assignment',
    ];

    public function readiness(AcademicTopic $topic): array
    {
        $topic->loadMissing('blocks');
        $checks = [
            'Topic mapped' => filled($topic->topic),
            'Sub-topic mapped' => filled($topic->sub_topic),
            'Week mapped' => filled($topic->week_number),
            'Entry behaviour' => filled($topic->entry_behaviour),
            'Previous knowledge' => filled($topic->previous_knowledge),
            'Instructional resources' => filled($topic->instructional_resources),
            'Introduction' => filled($topic->introduction),
            'Reference' => filled($topic->reference),
            'Objectives' => $topic->blocks->where('block_type', 'objective')->where('is_approved', true)->isNotEmpty(),
            'Presentation steps' => $topic->blocks->where('block_type', 'presentation')->where('is_approved', true)->isNotEmpty(),
            'Evaluation' => $topic->blocks->where('block_type', 'evaluation')->where('is_approved', true)->isNotEmpty(),
            'Assignment' => $topic->blocks->where('block_type', 'assignment')->where('is_approved', true)->isNotEmpty(),
            'Student-note content' => filled($topic->student_note_summary) || $topic->blocks->whereIn('block_type', ['definition', 'explanation', 'example', 'practical', 'note'])->where('is_approved', true)->isNotEmpty(),
        ];

        $complete = collect($checks)->filter()->count();
        $score = (int) round(($complete / count($checks)) * 100);

        return [
            'score' => $score,
            'ready' => $score >= 80 && $topic->status === 'approved',
            'checks' => $checks,
            'missing' => collect($checks)->filter(fn ($ok) => !$ok)->keys()->values()->all(),
        ];
    }

    public function lessonPlan(AcademicTopic $topic): array
    {
        $topic->loadMissing('blocks');
        $approved = $topic->blocks->where('is_approved', true);

        return [
            'class' => $topic->class_label,
            'subject' => $topic->subject_label,
            'week' => $topic->week_number,
            'lesson' => $topic->lesson_number,
            'topic' => $topic->topic,
            'sub_topic' => $topic->sub_topic,
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
