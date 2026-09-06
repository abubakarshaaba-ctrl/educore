<?php

namespace App\Services\Cbt;

use App\Models\CbtExam;
use App\Models\CbtIntegrityEvent;
use App\Models\CbtQuestion;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CbtStudentAttemptService
{
    public function __construct(
        private readonly CbtExamConfigurationService $configuration,
        private readonly CbtSubmissionService $submission,
    ) {}

    public function studentFor(User $user): Student
    {
        abort_unless($user->isStudent(), 403, 'Student CBT access only.');
        $student = Student::where('user_id', $user->id)->first();
        abort_unless($student && (int) $student->tenant_id === (int) $user->tenant_id, 403, 'No student profile is linked to this account.');

        return $student;
    }

    public function canTake(Student $student, CbtExam $exam): bool
    {
        return (int) $student->tenant_id === (int) $exam->tenant_id
            && $student->status === Student::STATUS_ACTIVE
            && $exam->isAssignedToClassArm($student->current_class_arm_id);
    }

    public function availableExams(Student $student): Collection
    {
        if ($student->status !== Student::STATUS_ACTIVE || ! $student->current_class_arm_id) {
            return collect();
        }

        $armId = (int) $student->current_class_arm_id;

        return CbtExam::with(['questionBank.subject', 'term', 'sections', 'classArms.classLevel'])
            ->where('status', 'published')
            ->where(fn ($query) => $query->where('class_arm_id', $armId)
                ->orWhereHas('classArms', fn ($assigned) => $assigned->where('class_arms.id', $armId)))
            ->orderByDesc('scheduled_start')->get();
    }

    public function preflight(CbtExam $exam, Student $student): array
    {
        abort_unless($this->canTake($student, $exam), 403, 'You are not allowed to access this exam.');
        abort_unless($exam->status === 'published', 409, 'This exam is not currently available.');

        $active = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)
            ->where('status', 'in_progress')->latest('attempt_number')->first();
        $latest = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)
            ->latest('attempt_number')->first();
        $retake = $exam->retakeAuthorizations()->where('student_id', $student->id)
            ->whereNull('used_at')->whereNull('revoked_at')->latest()->first();
        $window = $this->windowState($exam);
        $hasFinal = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)
            ->whereIn('status', CbtStudentSession::FINAL_STATUSES)->exists();

        return [
            'exam' => $this->examPayload($exam),
            'active_session_id' => $active?->id,
            'latest_attempt' => $latest ? $this->resultPayload($latest) : null,
            'window_state' => $window,
            'can_begin' => ! $active && $window === 'open' && (! $hasFinal || (bool) $retake),
            'can_resume' => (bool) $active,
            'retake_authorized' => (bool) $retake,
            'integrity_notice' => $this->integrityNotice($exam),
        ];
    }

    public function begin(CbtExam $exam, Student $student, ?string $ipAddress, ?string $userAgent): CbtStudentSession
    {
        abort_unless($this->canTake($student, $exam) && $exam->status === 'published', 403);
        abort_if($this->windowState($exam) === 'upcoming', 409, 'This exam has not started yet.');
        abort_if($this->windowState($exam) === 'closed', 409, 'This exam window has closed.');

        $session = DB::transaction(function () use ($exam, $student) {
            $active = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)
                ->where('status', 'in_progress')->lockForUpdate()->first();
            if ($active) {
                $active->update(['integrity_acknowledged_at' => $active->integrity_acknowledged_at ?: now(), 'started_at' => $active->started_at ?: now()]);

                return $active;
            }

            $lastAttempt = (int) CbtStudentSession::where('cbt_exam_id', $exam->id)
                ->where('student_id', $student->id)->lockForUpdate()->max('attempt_number');
            $authorization = null;
            if ($lastAttempt > 0) {
                $authorization = $exam->retakeAuthorizations()->where('student_id', $student->id)
                    ->where('attempt_number', $lastAttempt + 1)->whereNull('used_at')->whereNull('revoked_at')->lockForUpdate()->first();
                abort_unless($authorization, 403, 'A retake has not been authorized.');
            }

            if (! $exam->sections()->exists()) {
                $this->configuration->createSectionsFromBank($exam);
                $exam->refresh();
            }
            $questionIds = $this->configuration->questionIdsForAttempt($exam);
            abort_if(empty($questionIds), 422, 'This exam has no configured questions.');
            $created = CbtStudentSession::create([
                'tenant_id' => $student->tenant_id, 'cbt_exam_id' => $exam->id, 'student_id' => $student->id,
                'attempt_number' => $lastAttempt + 1, 'is_authorized_attempt' => $lastAttempt === 0 || (bool) $authorization,
                'retake_authorization_id' => $authorization?->id, 'question_order' => $questionIds,
                'answers' => [], 'essay_answers' => [], 'flagged_questions' => [], 'started_at' => now(),
                'integrity_acknowledged_at' => now(), 'status' => 'in_progress',
            ]);
            $authorization?->update(['used_at' => now()]);

            return $created;
        });

        CbtIntegrityEvent::firstOrCreate(
            ['cbt_student_session_id' => $session->id, 'event_type' => 'exam_started'],
            ['tenant_id' => $session->tenant_id, 'cbt_exam_id' => $session->cbt_exam_id, 'student_id' => $session->student_id,
                'event_uuid' => (string) Str::uuid(), 'severity' => 'info', 'ip_address' => $ipAddress,
                'user_agent' => $userAgent, 'metadata' => ['attempt_number' => $session->attempt_number], 'occurred_at' => now()]
        );

        return $session->fresh();
    }

    public function attemptPayload(CbtStudentSession $session): array
    {
        $session->loadMissing('exam.questionBank.subject');
        if ($session->isInProgress() && $this->remainingSeconds($session) <= 0) {
            $session = $this->submission->submit($session, [], [], 'time_expired', true);
        }
        if ($session->isFinal()) {
            return ['session' => $this->sessionState($session), 'sections' => [], 'result' => $this->resultPayload($session)];
        }

        $questions = $this->orderedQuestions($session->questionIds());
        $sections = $this->configuration->sectionPayload($session->exam, $questions)->map(function (array $entry) {
            $section = $entry['section'];

            return [
                'id' => $section->id, 'code' => $section->code, 'name' => $section->name, 'title' => $section->title,
                'instructions' => $section->instructions, 'answer_mode' => $section->answer_mode,
                'section_type' => $section->section_type, 'max_marks' => (float) $section->max_marks,
                'questions' => $entry['questions']->map(fn (CbtQuestion $question) => [
                    'id' => $question->id, 'parent_id' => $question->parent_question_id,
                    'display_path' => (string) $question->display_path, 'level' => (int) $question->display_level,
                    'type' => $question->type, 'text' => $question->question_text, 'html' => $question->question_html,
                    'options' => $section->answer_mode === 'online' ? $question->optionsArray() : [],
                    'marks' => (float) ($question->pivot?->marks_override ?? $question->marks ?? 0),
                    'requires_answer' => (bool) $question->requires_answer,
                    'instruction_only' => (bool) $question->is_instruction_only,
                    'has_image' => (bool) $question->image_path,
                ])->values(),
            ];
        })->values();

        return ['session' => $this->sessionState($session), 'sections' => $sections, 'result' => null];
    }

    public function save(CbtStudentSession $session, array $answers, array $flagged, string $version): CbtStudentSession
    {
        $session->loadMissing('exam');
        if ($this->remainingSeconds($session) <= 0) {
            return $this->submission->submit($session, $answers, [], 'time_expired', true);
        }

        return DB::transaction(function () use ($session, $answers, $flagged, $version) {
            $locked = CbtStudentSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->isFinal(), 409, 'This attempt is already final.');
            abort_unless(hash_equals((string) $locked->sync_version, $version), 409, 'This attempt changed on the server. Refresh before saving again.');
            $allowed = array_flip($locked->questionIds());
            $answers = array_intersect_key($answers, $allowed);
            $flagged = array_values(array_filter(array_map('intval', $flagged), fn (int $id) => isset($allowed[$id])));
            $locked->update([
                'answers' => array_replace((array) $locked->answers, $answers),
                'flagged_questions' => $flagged,
                'last_synced_at' => now(),
                'sync_version' => ((int) $locked->sync_version) + 1,
            ]);

            return $locked->fresh();
        });
    }

    public function submit(CbtStudentSession $session, array $answers): CbtStudentSession
    {
        abort_if($session->isFinal(), 409, 'This exam has already been submitted.');
        abort_unless($session->isInProgress(), 409, 'This exam session is no longer active.');

        return $this->submission->submit($session, $answers, []);
    }

    public function examPayload(CbtExam $exam): array
    {
        $exam->loadMissing(['questionBank.subject', 'term', 'sections']);

        return [
            'id' => $exam->id, 'title' => $exam->title, 'subject' => $exam->questionBank?->subject?->name,
            'duration_minutes' => (int) $exam->duration_minutes, 'total_questions' => (int) $exam->total_questions,
            'total_marks' => (float) $exam->total_marks, 'scheduled_start' => $exam->scheduled_start?->toIso8601String(),
            'scheduled_end' => $exam->scheduled_end?->toIso8601String(), 'status' => $exam->status,
            'malpractice_enabled' => (bool) $exam->malpractice_enabled, 'require_fullscreen' => (bool) $exam->require_fullscreen,
            'focus_loss_policy' => $exam->focus_loss_policy, 'max_focus_losses' => (int) $exam->max_focus_losses,
            'sections' => $exam->sections->where('is_active', true)->map(fn ($section) => [
                'id' => $section->id, 'code' => $section->code, 'name' => $section->name,
                'answer_mode' => $section->answer_mode, 'max_marks' => (float) $section->max_marks,
            ])->values(),
        ];
    }

    public function resultPayload(CbtStudentSession $session): array
    {
        return [
            'session_id' => $session->id, 'status' => $session->status, 'attempt_number' => (int) $session->attempt_number,
            'submitted_at' => $session->submitted_at?->toIso8601String(), 'fully_scored' => $session->isFullyScored(),
            'score' => $session->isFullyScored() ? (float) $session->raw_score : null,
            'maximum_score' => $session->isFullyScored() ? (float) $session->maximum_score : null,
            'percentage' => $session->isFullyScored() ? (float) $session->percentage : null,
        ];
    }

    private function sessionState(CbtStudentSession $session): array
    {
        return [
            'id' => $session->id, 'exam_id' => $session->cbt_exam_id, 'status' => $session->status,
            'attempt_number' => (int) $session->attempt_number, 'version' => (string) $session->sync_version,
            'started_at' => $session->started_at?->toIso8601String(), 'deadline_at' => $this->deadline($session)?->toIso8601String(),
            'remaining_seconds' => $this->remainingSeconds($session), 'server_time' => now()->toIso8601String(),
            'answers' => (object) ((array) $session->answers), 'flagged_questions' => array_values((array) $session->flagged_questions),
            'focus_loss_count' => (int) $session->focus_loss_count,
        ];
    }

    private function orderedQuestions(array $ids): Collection
    {
        $questions = CbtQuestion::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $questions->get((int) $id))->filter()->values();
    }

    private function deadline(CbtStudentSession $session)
    {
        $session->loadMissing('exam');
        $deadline = $session->started_at?->copy()->addMinutes((int) $session->exam->duration_minutes);
        if ($session->exam->scheduled_end && (! $deadline || $session->exam->scheduled_end->lt($deadline))) {
            $deadline = $session->exam->scheduled_end;
        }

        return $deadline;
    }

    private function remainingSeconds(CbtStudentSession $session): int
    {
        $deadline = $this->deadline($session);

        return $deadline ? max(0, now()->diffInSeconds($deadline, false)) : 0;
    }

    private function windowState(CbtExam $exam): string
    {
        if ($exam->scheduled_start && now()->lt($exam->scheduled_start)) {
            return 'upcoming';
        }
        if ($exam->scheduled_end && now()->gte($exam->scheduled_end)) {
            return 'closed';
        }

        return 'open';
    }

    private function integrityNotice(CbtExam $exam): array
    {
        return [
            'secure_screen_required' => true,
            'fullscreen_required' => (bool) $exam->require_fullscreen,
            'focus_loss_policy' => $exam->malpractice_enabled ? $exam->focus_loss_policy : 'log',
            'max_focus_losses' => (int) $exam->max_focus_losses,
            'message' => 'Keep EduCore visible until submission. Leaving the exam may be recorded and can automatically submit the attempt under your school’s policy.',
        ];
    }
}
