<?php

namespace App\Services;

use App\Models\AcademicTopic;
use App\Models\ClassLevel;
use App\Models\LessonPlan;
use App\Models\LessonPlanSource;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicTopicLessonPlanService
{
    public function __construct(private AcademicRepositoryKnowledgeService $knowledge)
    {
    }

    public function save(AcademicTopic $topic, User $user): LessonPlan
    {
        $topic->loadMissing(['blocks', 'source']);
        $readiness = $this->knowledge->readiness($topic);

        if ($topic->status !== 'approved' || !$readiness['ready']) {
            throw ValidationException::withMessages([
                'topic' => 'This knowledge topic must be approved and generation-ready before it can be saved to Lesson Planner.',
            ]);
        }

        $existing = LessonPlan::query()
            ->where('teacher_id', $user->id)
            ->where('academic_topic_id', $topic->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $subject = $this->matchSubject($topic->subject_label, $user);
        $classLevel = $this->matchClassLevel($topic->class_label, $user);
        $term = $this->matchTerm($topic->term_label, $user);
        $document = $this->knowledge->lessonPlan($topic);

        return DB::transaction(function () use ($topic, $user, $subject, $classLevel, $term, $document) {
            $plan = LessonPlan::create([
                'teacher_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'subject_id' => $subject->id,
                'class_level_id' => $classLevel->id,
                'class_arm_id' => null,
                'term_id' => $term->id,
                'curriculum_type' => 'nerdc',
                'topic' => $document['topic'],
                'subtopic' => $document['sub_topic'],
                'week_number' => $document['week'],
                'lesson_number' => $document['lesson'],
                'lesson_time' => $document['time'],
                'duration_minutes' => $document['duration_minutes'],
                'average_age' => $document['average_age'],
                'sex' => $document['sex'],
                'entry_behaviour' => $document['entry_behaviour'],
                'academic_topic_id' => $topic->id,
                'previous_knowledge' => $document['previous_knowledge'],
                'behavioural_objectives' => $this->lines($document['behavioural_objectives']),
                'instructional_materials' => $document['instructional_resources'],
                'reference_materials' => $document['reference'],
                'set_induction' => $document['introduction'],
                'presentation' => collect($document['presentation'])->map(function (array $step) {
                    return trim(($step['title'] ?? '')."\n".($step['content'] ?? ''));
                })->filter()->implode("\n\n"),
                'evaluation' => $this->lines($document['evaluation']),
                'assignment' => $this->lines($document['assignment']),
                'status' => 'draft',
                'published_at' => null,
                'ai_generated' => false,
                'delivery_type' => 'regular',
                'structured_plan' => [
                    'generation_method' => 'academic_repository_deterministic',
                    'academic_topic_id' => $topic->id,
                    'readiness_score' => $this->knowledge->readiness($topic)['score'],
                    'document' => $document,
                    'repository_context' => $topic->source ? [[
                        'source_id' => $topic->source->id,
                        'title' => $topic->source->title,
                        'original_filename' => $topic->source->original_filename,
                    ]] : [],
                ],
                'source_document_ids' => $topic->curriculum_source_id ? [$topic->curriculum_source_id] : [],
                'grounding_score' => $this->knowledge->readiness($topic)['score'],
                'grounding_summary' => [
                    'method' => 'deterministic_repository',
                    'academic_topic_id' => $topic->id,
                    'source_id' => $topic->curriculum_source_id,
                ],
            ]);

            if ($topic->curriculum_source_id) {
                LessonPlanSource::create([
                    'lesson_plan_id' => $plan->id,
                    'curriculum_source_id' => $topic->curriculum_source_id,
                    'curriculum_fragment_id' => null,
                    'rank' => 1,
                    'generation_type' => 'lesson_plan',
                ]);
            }

            return $plan;
        });
    }

    private function matchSubject(string $label, User $user): Subject
    {
        $query = Subject::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && $this->hasColumn((new Subject)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        return $query->first() ?: throw ValidationException::withMessages([
            'subject' => "No school subject exactly matches '{$label}'. Map this knowledge topic to an existing subject first.",
        ]);
    }

    private function matchClassLevel(string $label, User $user): ClassLevel
    {
        $query = ClassLevel::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && $this->hasColumn((new ClassLevel)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        return $query->first() ?: throw ValidationException::withMessages([
            'class' => "No school class level exactly matches '{$label}'. Map this knowledge topic to an existing class first.",
        ]);
    }

    private function matchTerm(string $label, User $user): Term
    {
        $query = Term::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && $this->hasColumn((new Term)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        return $query->orderByDesc('id')->first() ?: throw ValidationException::withMessages([
            'term' => "No school term exactly matches '{$label}'. Map this knowledge topic to an existing term first.",
        ]);
    }

    private function lines(array $items): ?string
    {
        $value = collect($items)->map(fn ($item) => trim((string) $item))->filter()->values()->map(fn ($item, $index) => ($index + 1).'. '.$item)->implode("\n");
        return $value !== '' ? $value : null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }
}
