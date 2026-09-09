<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CbtExam;
use App\Models\CbtQuestionBank;
use App\Models\CbtStudentSession;
use App\Models\ClassArmSubject;
use App\Services\Cbt\CbtExamConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StaffCbtApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authorise($request);
        $status = trim((string) $request->query('status', ''));
        $query = trim((string) $request->query('query', ''));

        $exams = $this->scopedExams($user)
            ->when($status !== '', fn (Builder $builder) => $builder->where('status', $status))
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $nested) use ($query): void {
                    $nested->where('title', 'like', "%{$query}%")
                        ->orWhereHas('questionBank.subject', fn (Builder $subject) => $subject->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('classArms', fn (Builder $arm) => $arm->where('name', 'like', "%{$query}%"));
                });
            })
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'capabilities' => [
                'full_access' => $this->hasFullAccess($user),
                'create_exam' => true,
                'publish_exam' => true,
                'close_exam' => true,
                'reschedule_exam' => true,
            ],
            'counts' => [
                'all' => $exams->count(),
                'draft' => $exams->where('status', 'draft')->count(),
                'published' => $exams->whereIn('status', ['published', 'active'])->count(),
                'closed' => $exams->where('status', 'closed')->count(),
            ],
            'exams' => $exams->map(fn (CbtExam $exam) => $this->payload($exam))->values(),
        ]);
    }

    public function show(Request $request, CbtExam $exam)
    {
        $user = $this->authorise($request);
        $this->authoriseExam($user, $exam);

        $exam->load([
            'questionBank.subject', 'questionBank.classLevel', 'classArm.classLevel',
            'classArms.classLevel', 'term.session', 'sections',
        ])->loadCount('studentSessions');

        $submitted = CbtStudentSession::where('cbt_exam_id', $exam->id)->whereNotNull('submitted_at')->count();
        $graded = CbtStudentSession::where('cbt_exam_id', $exam->id)->whereNotNull('grading_completed_at')->count();

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'exam' => array_merge($this->payload($exam), [
                'sections' => $exam->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'title' => $section->name ?? $section->title ?? 'Section',
                    'display_order' => (int) ($section->display_order ?? 0),
                ])->values(),
                'attempts' => [
                    'total' => (int) ($exam->student_sessions_count ?? 0),
                    'submitted' => $submitted,
                    'graded' => $graded,
                ],
            ]),
        ]);
    }

    public function publish(Request $request, CbtExam $exam, CbtExamConfigurationService $configuration)
    {
        $user = $this->authorise($request);
        $this->authoriseExam($user, $exam);
        abort_unless($exam->status === 'draft', 422, 'Only draft exams can be published.');

        $configuration->createSectionsFromBank($exam, $user->id);
        $errors = $configuration->publicationErrors($exam->fresh());
        if ($errors) {
            return response()->json(['message' => 'The exam is not ready to publish.', 'errors' => $errors], 422);
        }

        $exam->update(['status' => 'published']);
        $this->audit($exam, $user->id, 'cbt.exam.published', null, ['status' => 'published']);

        return response()->json([
            'message' => 'Exam published. Students can now access it.',
            'exam' => $this->payload($exam->fresh()),
        ]);
    }

    public function close(Request $request, CbtExam $exam)
    {
        $user = $this->authorise($request);
        $this->authoriseExam($user, $exam);
        abort_unless(in_array($exam->status, ['published', 'active'], true), 422, 'Only a published or active exam can be closed.');

        $old = ['status' => $exam->status];
        $exam->update(['status' => 'closed']);
        $this->audit($exam, $user->id, 'cbt.exam.closed', $old, ['status' => 'closed']);

        CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->whereNotNull('grading_completed_at')
            ->get()
            ->each(fn (CbtStudentSession $attempt) => app(\App\Services\Cbt\CbtResultSyncService::class)->sync($attempt));

        return response()->json([
            'message' => 'Exam closed. No more submissions are allowed.',
            'exam' => $this->payload($exam->fresh()),
        ]);
    }

    public function reschedule(Request $request, CbtExam $exam)
    {
        $user = $this->authorise($request);
        $this->authoriseExam($user, $exam);
        abort_unless(in_array($exam->status, ['published', 'active', 'closed'], true), 422, 'Only an ongoing or closed exam can be rescheduled.');

        $data = $request->validate([
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);

        $old = [
            'status' => $exam->status,
            'scheduled_start' => $exam->scheduled_start?->toIso8601String(),
            'scheduled_end' => $exam->scheduled_end?->toIso8601String(),
            'duration_minutes' => (int) $exam->duration_minutes,
        ];

        $exam->update($data + ['status' => 'published']);
        $fresh = $exam->fresh();
        $this->audit($fresh, $user->id, 'cbt.exam.rescheduled', $old, [
            'status' => 'published',
            'scheduled_start' => $fresh->scheduled_start?->toIso8601String(),
            'scheduled_end' => $fresh->scheduled_end?->toIso8601String(),
            'duration_minutes' => (int) $fresh->duration_minutes,
        ]);

        return response()->json([
            'message' => $old['status'] === 'closed' ? 'Exam rescheduled and reopened.' : 'Exam schedule updated.',
            'exam' => $this->payload($fresh),
        ]);
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
        return ClassArmSubject::where('teacher_id', $user->id)->pluck('subject_id')->unique();
    }

    private function teacherTeachesBank($user, CbtQuestionBank $bank): bool
    {
        if ($this->hasFullAccess($user)) return true;
        return ClassArmSubject::where('teacher_id', $user->id)
            ->where('subject_id', $bank->subject_id)
            ->whereHas('classArm', fn (Builder $query) => $query->where('class_level_id', $bank->class_level_id))
            ->exists();
    }

    private function scopedExams($user): Builder
    {
        $query = CbtExam::with([
            'questionBank.subject', 'questionBank.classLevel', 'classArm.classLevel',
            'classArms.classLevel', 'term.session',
        ])->withCount('studentSessions')
            ->where('tenant_id', $user->tenant_id);

        if (! $this->hasFullAccess($user)) {
            $bankIds = CbtQuestionBank::where('tenant_id', $user->tenant_id)
                ->whereIn('subject_id', $this->teacherSubjectIds($user))->pluck('id');
            $query->whereIn('question_bank_id', $bankIds);
        }
        return $query;
    }

    private function authoriseExam($user, CbtExam $exam): void
    {
        abort_unless((int) $exam->tenant_id === (int) $user->tenant_id, 404);
        $exam->loadMissing('questionBank');
        abort_unless($exam->questionBank && $this->teacherTeachesBank($user, $exam->questionBank), 403, 'You can only manage exams for subjects you teach.');
    }

    private function payload(CbtExam $exam): array
    {
        $exam->loadMissing([
            'questionBank.subject', 'questionBank.classLevel', 'classArm.classLevel',
            'classArms.classLevel', 'term.session',
        ]);

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'status' => $exam->status,
            'duration_minutes' => (int) $exam->duration_minutes,
            'total_questions' => (int) $exam->total_questions,
            'total_marks' => (float) $exam->total_marks,
            'scheduled_start' => $exam->scheduled_start?->toIso8601String(),
            'scheduled_end' => $exam->scheduled_end?->toIso8601String(),
            'subject' => $exam->questionBank?->subject ? ['id' => $exam->questionBank->subject->id, 'name' => $exam->questionBank->subject->name] : null,
            'class_level' => $exam->questionBank?->classLevel ? ['id' => $exam->questionBank->classLevel->id, 'name' => $exam->questionBank->classLevel->name] : null,
            'classes' => $exam->classArms->map(fn ($arm) => ['id' => $arm->id, 'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name)])->values(),
            'term' => $exam->term ? ['id' => $exam->term->id, 'name' => $exam->term->name, 'session' => $exam->term->session?->name] : null,
            'attempts_count' => (int) ($exam->student_sessions_count ?? 0),
            'can_publish' => $exam->status === 'draft',
            'can_close' => in_array($exam->status, ['published', 'active'], true),
            'can_reschedule' => in_array($exam->status, ['published', 'active', 'closed'], true),
        ];
    }

    private function audit(CbtExam $exam, int $actorId, string $action, ?array $old, array $new): void
    {
        AuditLog::create([
            'tenant_id' => $exam->tenant_id,
            'actor_user_id' => $actorId,
            'auditable_type' => CbtExam::class,
            'auditable_id' => $exam->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
