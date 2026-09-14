<?php

namespace App\Services;

use App\Models\AcademicTopic;
use App\Models\ClassLevel;
use App\Models\LessonPlan;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        if ($topic->status !== 'approved' || ! $readiness['ready']) {
            throw ValidationException::withMessages([
                'topic' => 'This knowledge topic is not generation-ready. Resolve the missing or quality-critical items before saving it to Lesson Planner.',
            ]);
        }

        $existing = LessonPlan::query()
            ->where('tenant_id', $user->tenant_id)
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

        return DB::transaction(function () use ($topic, $user, $subject, $classLevel, $term, $document, $readiness) {
            return LessonPlan::create([
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
                'duration_minutes' => $document['duration'],
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
                    'readiness_score' => $readiness['score'],
                    'coverage_score' => $readiness['coverage_score'],
                    'quality_score' => $readiness['quality_score'],
                    'quality_issues' => $readiness['quality']['issues'],
                    'document' => $document,
                    'repository_context' => $readiness['sources'],
                ],
            ]);
        });
    }

    private function matchSubject(string $label, User $user): Subject
    {
        $query = Subject::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && Schema::hasColumn((new Subject)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->first() ?: throw ValidationException::withMessages([
            'subject' => "No subject in this school exactly matches '{$label}'. Map the repository topic to an existing school subject first.",
        ]);
    }

    private function matchClassLevel(string $label, User $user): ClassLevel
    {
        $query = ClassLevel::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && Schema::hasColumn((new ClassLevel)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->first() ?: throw ValidationException::withMessages([
            'class' => "No class level in this school exactly matches '{$label}'. Map the repository topic to an existing school class first.",
        ]);
    }

    private function matchTerm(string $label, User $user): Term
    {
        $query = Term::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($label))]);
        if ($user->tenant_id && Schema::hasColumn((new Term)->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->orderByDesc('id')->first() ?: throw ValidationException::withMessages([
            'term' => "No term in this school exactly matches '{$label}'. Map the repository topic to an existing school term first.",
        ]);
    }

    private function lines(array $items): ?string
    {
        $value = collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->map(fn ($item, $index) => ($index + 1).'. '.$item)
            ->implode("\n");

        return $value !== '' ? $value : null;
    }
}
