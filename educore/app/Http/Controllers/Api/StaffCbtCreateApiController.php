<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use App\Models\AuditLog;
use App\Models\CbtExam;
use App\Models\CbtQuestionBank;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\Term;
use App\Services\Cbt\CbtExamConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffCbtCreateApiController extends Controller
{
    public function options(Request $request)
    {
        $user = $this->authorise($request);
        $tenantId = (int) $user->tenant_id;

        $banks = CbtQuestionBank::query()
            ->where('tenant_id', $tenantId)
            ->with(['subject:id,name', 'classLevel:id,name'])
            ->withCount('questions')
            ->when(! $this->hasFullAccess($user), fn (Builder $query) => $query->whereIn('subject_id', $this->teacherSubjectIds($user)))
            ->orderBy('name')->get()
            ->filter(fn (CbtQuestionBank $bank) => $this->teacherTeachesBank($user, $bank))
            ->map(fn (CbtQuestionBank $bank) => [
                'id' => $bank->id,
                'name' => $bank->name,
                'subject' => $bank->subject ? ['id' => $bank->subject->id, 'name' => $bank->subject->name] : null,
                'class_level' => $bank->classLevel ? ['id' => $bank->classLevel->id, 'name' => $bank->classLevel->name] : null,
                'question_count' => (int) $bank->questions_count,
            ])->values();

        $arms = ClassArm::query()
            ->where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->when(! $this->hasFullAccess($user), fn (Builder $query) => $query->whereIn('id', $this->teacherClassArmIds($user)))
            ->orderBy('class_level_id')->orderBy('name')->get()
            ->map(fn (ClassArm $arm) => [
                'id' => $arm->id,
                'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                'class_level_id' => (int) $arm->class_level_id,
            ])->values();

        $terms = Term::where('tenant_id', $tenantId)->with('session:id,name')->latest('id')->get()
            ->map(fn (Term $term) => [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
                'is_current' => (bool) $term->is_current,
            ])->values();

        $assessmentTypes = AssessmentType::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('term_id')->orderBy('name')->get()
            ->map(fn (AssessmentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'term_id' => (int) $type->term_id,
                'is_exam' => (bool) ($type->is_exam ?? false),
                'weight_percentage' => isset($type->weight_percentage) ? (float) $type->weight_percentage : null,
            ])->values();

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'defaults' => [
                'term_id' => $terms->firstWhere('is_current', true)['id'] ?? $terms->first()['id'] ?? null,
                'duration_minutes' => 60,
                'malpractice_enabled' => true,
                'focus_loss_policy' => 'submit',
                'max_focus_losses' => 0,
                'require_fullscreen' => false,
            ],
            'banks' => $banks,
            'classes' => $arms,
            'terms' => $terms,
            'assessment_types' => $assessmentTypes,
        ]);
    }

    public function store(Request $request, CbtExamConfigurationService $configuration)
    {
        $user = $this->authorise($request);
        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'question_bank_id' => ['required', 'integer'],
            'class_arm_ids' => ['required', 'array', 'min:1', 'max:50'],
            'class_arm_ids.*' => ['required', 'integer', 'distinct'],
            'term_id' => ['required', 'integer'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'scheduled_start' => ['nullable', 'date', 'required_with:scheduled_end'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start', 'required_with:scheduled_start'],
            'assessment_type_id' => ['nullable', 'integer'],
            'malpractice_enabled' => ['nullable', 'boolean'],
            'focus_loss_policy' => ['nullable', 'in:submit,warn,log'],
            'max_focus_losses' => ['nullable', 'integer', 'min:0', 'max:20'],
            'require_fullscreen' => ['nullable', 'boolean'],
        ]);

        $bank = CbtQuestionBank::with('classLevel')->where('tenant_id', $tenantId)->findOrFail($validated['question_bank_id']);
        abort_unless($this->teacherTeachesBank($user, $bank), 403, 'You can only create exams for subjects you teach.');

        $arms = ClassArm::with('classLevel')->where('tenant_id', $tenantId)->whereIn('id', $validated['class_arm_ids'])->get();
        if ($arms->count() !== count($validated['class_arm_ids'])) {
            throw ValidationException::withMessages(['class_arm_ids' => 'One or more selected classes are unavailable.']);
        }
        if ($arms->contains(fn (ClassArm $arm) => (int) $arm->class_level_id !== (int) $bank->class_level_id)) {
            throw ValidationException::withMessages(['class_arm_ids' => 'Every selected class must belong to the question bank class level.']);
        }
        if (! $this->hasFullAccess($user)) {
            $allowedArmIds = $this->teacherClassArmIds($user);
            if ($arms->contains(fn (ClassArm $arm) => ! $allowedArmIds->contains((int) $arm->id))) {
                abort(403, 'You can only assign exams to classes you teach.');
            }
        }

        $term = Term::where('tenant_id', $tenantId)->findOrFail($validated['term_id']);
        if (! empty($validated['assessment_type_id'])) {
            $assessmentType = AssessmentType::where('tenant_id', $tenantId)->findOrFail($validated['assessment_type_id']);
            if ((int) $assessmentType->term_id !== (int) $term->id) {
                throw ValidationException::withMessages(['assessment_type_id' => 'The selected assessment type does not belong to the selected term.']);
            }
        }

        $title = trim((string) preg_replace('/\s+/', ' ', $validated['title']));
        [$exam, $reused] = DB::transaction(function () use ($request, $validated, $arms, $title, $user, $tenantId) {
            $exam = CbtExam::where('tenant_id', $tenantId)
                ->where('question_bank_id', $validated['question_bank_id'])
                ->where('term_id', $validated['term_id'])
                ->where('status', 'draft')->where('title', $title)
                ->lockForUpdate()->oldest('id')->first();
            $reused = (bool) $exam;

            $attributes = [
                'tenant_id' => $tenantId,
                'title' => $title,
                'question_bank_id' => $validated['question_bank_id'],
                'class_arm_id' => $exam?->class_arm_id ?: $arms->first()->id,
                'term_id' => $validated['term_id'],
                'assessment_type_id' => $validated['assessment_type_id'] ?? null,
                'section_objective_count' => 0,
                'section_objective_marks' => 1,
                'section_theory_count' => 0,
                'section_theory_marks' => 1,
                'duration_minutes' => $validated['duration_minutes'],
                'scheduled_start' => $validated['scheduled_start'] ?? null,
                'scheduled_end' => $validated['scheduled_end'] ?? null,
                'shuffle_questions' => true,
                'shuffle_options' => true,
                'status' => 'draft',
                'created_by' => $user->id,
                'malpractice_enabled' => $request->boolean('malpractice_enabled', true),
                'focus_loss_policy' => $validated['focus_loss_policy'] ?? 'submit',
                'max_focus_losses' => $validated['max_focus_losses'] ?? 0,
                'require_fullscreen' => $request->boolean('require_fullscreen'),
                'retake_policy' => 'latest_valid_authorized_attempt',
                'strict_marks_validation' => true,
            ];

            if ($exam) $exam->update($attributes);
            else $exam = CbtExam::create($attributes + ['total_questions' => 0, 'total_marks' => 0]);

            $assignments = $arms->mapWithKeys(fn (ClassArm $arm) => [$arm->id => ['tenant_id' => $tenantId]])->all();
            $exam->classArms()->sync($assignments);
            return [$exam, $reused];
        });

        $configuration->createSectionsFromBank($exam, $user->id);
        $fresh = $exam->fresh()->load(['questionBank.subject', 'classArms.classLevel', 'term.session', 'sections']);

        AuditLog::create([
            'tenant_id' => $fresh->tenant_id,
            'actor_user_id' => $user->id,
            'auditable_type' => CbtExam::class,
            'auditable_id' => $fresh->id,
            'action' => $reused ? 'cbt.exam.draft_reused' : 'cbt.exam.created',
            'new_values' => [
                'title' => $fresh->title,
                'question_bank_id' => $fresh->question_bank_id,
                'class_arm_ids' => $fresh->assignedClassArmIds()->all(),
                'term_id' => $fresh->term_id,
                'duration_minutes' => $fresh->duration_minutes,
            ],
        ]);

        return response()->json([
            'message' => $reused ? 'The existing draft was reused and updated.' : 'Examination draft created.',
            'exam_id' => $fresh->id,
            'reused' => $reused,
            'section_count' => $fresh->sections->count(),
            'question_count' => (int) $fresh->total_questions,
        ], $reused ? 200 : 201);
    }

    private function authorise(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && ! $user->isStudent() && ! $user->isParent() && ! $user->isSuperAdmin() && $user->canAccessModule('cbt'),
            403,
            'You are not authorized to access school CBT management.'
        );
        return $user;
    }

    private function hasFullAccess($user): bool
    {
        if ($user->isAdmin()) return true;
        return ! in_array($user->roleKey(), ['subject_teacher', 'teacher', 'form_subject_teacher'], true);
    }

    private function teacherSubjectIds($user)
    {
        return ClassArmSubject::where('teacher_id', $user->id)->pluck('subject_id')->map(fn ($id) => (int) $id)->unique();
    }

    private function teacherClassArmIds($user)
    {
        return ClassArmSubject::where('teacher_id', $user->id)->pluck('class_arm_id')->map(fn ($id) => (int) $id)->unique();
    }

    private function teacherTeachesBank($user, CbtQuestionBank $bank): bool
    {
        if ($this->hasFullAccess($user)) return true;
        return ClassArmSubject::where('teacher_id', $user->id)
            ->where('subject_id', $bank->subject_id)
            ->whereHas('classArm', fn (Builder $query) => $query->where('class_level_id', $bank->class_level_id))
            ->exists();
    }
}
