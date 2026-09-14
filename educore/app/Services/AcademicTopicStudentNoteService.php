<?php

namespace App\Services;

use App\Models\AcademicTopic;
use App\Models\LessonNoteRevision;
use App\Models\User;
use App\Services\LessonPlanning\StructuredNoteRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicTopicStudentNoteService
{
    public function __construct(
        private AcademicRepositoryKnowledgeService $knowledge,
        private AcademicTopicLessonPlanService $lessonPlans,
        private StructuredNoteRenderer $renderer,
    ) {
    }

    public function save(AcademicTopic $topic, User $user): LessonNoteRevision
    {
        $topic->loadMissing(['blocks', 'source']);
        $readiness = $this->knowledge->readiness($topic);

        if ($topic->status !== 'approved' || ! $readiness['ready']) {
            throw ValidationException::withMessages([
                'topic' => 'This repository topic is not generation-ready. Resolve the missing or quality-critical items before saving its student note.',
            ]);
        }

        $plan = $this->lessonPlans->save($topic, $user);
        $document = $this->knowledge->studentNote($topic);
        $content = $this->toStructuredNote($topic, $document);
        $signature = hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $existing = $plan->noteRevisions()
            ->latest('revision')
            ->limit(10)
            ->get()
            ->first(function (LessonNoteRevision $revision) use ($topic, $signature) {
                $trace = is_array($revision->source_trace) ? $revision->source_trace : [];

                return ! $revision->teacher_edited
                    && ($trace['generation_method'] ?? null) === 'academic_repository_deterministic'
                    && (int) ($trace['academic_topic_id'] ?? 0) === (int) $topic->id
                    && ($trace['source_signature'] ?? null) === $signature;
            });

        if ($existing) {
            return $existing;
        }

        $sourceTrace = [
            'generation_method' => 'academic_repository_deterministic',
            'academic_topic_id' => $topic->id,
            'curriculum_source_id' => $topic->curriculum_source_id,
            'source_signature' => $signature,
            'source_title' => $topic->source?->title,
            'source_filename' => $topic->source?->original_filename,
            'readiness_score' => $readiness['score'],
            'coverage_score' => $readiness['coverage_score'],
            'quality_score' => $readiness['quality_score'],
            'quality_issues' => $readiness['quality']['issues'],
            'ranked_sources' => $readiness['sources'],
        ];

        $content['source_trace'] = $sourceTrace;

        return DB::transaction(function () use ($plan, $user, $content, $sourceTrace) {
            $revisionNumber = ((int) LessonNoteRevision::query()
                ->where('lesson_plan_id', $plan->id)
                ->lockForUpdate()
                ->max('revision')) + 1;

            $revision = LessonNoteRevision::create([
                'tenant_id' => $plan->tenant_id,
                'lesson_plan_id' => $plan->id,
                'revision' => $revisionNumber,
                'status' => 'draft',
                'depth' => 'standard',
                'content' => $content,
                'source_trace' => $sourceTrace,
                'ai_generated' => false,
                'teacher_edited' => false,
                'created_by' => $user->id,
            ]);

            $plan->update([
                'current_note_revision' => $revisionNumber,
                'note_depth' => 'standard',
                'lesson_notes' => $this->renderer->toHtml($content),
            ]);

            return $revision;
        });
    }

    private function toStructuredNote(AcademicTopic $topic, array $document): array
    {
        $sections = collect($document['content'] ?? [])->map(function (array $section) {
            return [
                'heading' => trim((string) ($section['heading'] ?? 'Lesson Content')),
                'subheading' => null,
                'content_blocks' => [[
                    'type' => 'paragraph',
                    'content' => trim((string) ($section['content'] ?? '')),
                ]],
            ];
        })->filter(fn (array $section) => filled($section['content_blocks'][0]['content'] ?? null))->values()->all();

        if (filled($document['summary'] ?? null)) {
            array_unshift($sections, [
                'heading' => 'Overview',
                'subheading' => null,
                'content_blocks' => [[
                    'type' => 'paragraph',
                    'content' => trim((string) $document['summary']),
                ]],
            ]);
        }

        if (! empty($document['objectives'])) {
            array_unshift($sections, [
                'heading' => 'Learning Objectives',
                'subheading' => null,
                'content_blocks' => [[
                    'type' => 'bullets',
                    'items' => array_values($document['objectives']),
                ]],
            ]);
        }

        return [
            'week' => (string) ($document['week'] ?? $topic->week_number ?? ''),
            'lesson' => (string) ($topic->lesson_number ?? '1'),
            'topic' => (string) ($document['topic'] ?? $topic->topic),
            'sub_topics' => filled($document['sub_topic'] ?? null) ? [(string) $document['sub_topic']] : [],
            'sections' => $sections,
            'evaluation' => array_values($document['review_questions'] ?? []),
            'assignment' => $this->lines($document['assignment'] ?? []),
            'reading_assignment' => trim((string) ($document['reference'] ?? '')),
        ];
    }

    private function lines(array $items): string
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->map(fn ($item, $index) => ($index + 1).'. '.$item)
            ->implode("\n");
    }
}
