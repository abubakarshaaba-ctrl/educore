<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ExamSupervisor;
use App\Models\ExamTimetableEntry;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetablePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileScheduleController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['nullable', 'integer'],
            'child_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $user = $request->user();
        $session = AcademicSession::current()->first();
        $term = Term::current()->with('session')->first();
        $from = isset($data['from']) ? now()->parse($data['from'])->startOfDay() : today();
        $to = isset($data['to']) ? now()->parse($data['to'])->endOfDay() : today()->addDays(45)->endOfDay();

        $context = $this->context($request, $data['class_arm_id'] ?? null, $data['child_id'] ?? null);
        $periods = $this->periods($user, $context['class'], $session?->id, $context['scope']);
        $exams = $context['class']
            ? $this->examSchedule($context['class'], $term?->id, $from->toDateString(), $to->toDateString())
            : collect();
        $duties = $user->isTenantStaff()
            ? $this->duties($user->id, $term?->id, $from->toDateString(), $to->toDateString())
            : collect();

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'session' => $session?->only(['id', 'name']),
            'term' => $term ? ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name] : null,
            'scope' => [
                'type' => $context['scope'],
                'title' => $context['title'],
                'class' => $context['class'] ? [
                    'id' => $context['class']->id,
                    'name' => $this->className($context['class']),
                ] : null,
            ],
            'week' => collect(self::DAYS)->map(fn (string $day) => [
                'day' => $day,
                'periods' => $periods->filter(fn (TimetablePeriod $period) => strcasecmp((string) $period->day_of_week, $day) === 0)
                    ->sortBy('start_time')->map(fn (TimetablePeriod $period) => $this->periodPayload($period))->values(),
            ])->values(),
            'exams' => $exams->values(),
            'duties' => $duties->values(),
        ]);
    }

    private function context(Request $request, ?int $requestedClassId, ?int $childId): array
    {
        $user = $request->user();
        if ($user->isStudent()) {
            $student = Student::with('currentClassArm.classLevel')->where('user_id', $user->id)->first();
            abort_unless($student, 403, 'No student profile is linked to this account.');

            return ['scope' => 'student', 'title' => $student->full_name, 'class' => $student->currentClassArm];
        }

        if ($user->isParent()) {
            $guardian = Guardian::where('user_id', $user->id)->first();
            abort_unless($guardian, 403, 'No guardian profile is linked to this account.');
            $children = $guardian->students()->with('currentClassArm.classLevel')->where('students.tenant_id', $user->tenant_id)->get();
            $student = $childId ? $children->firstWhere('id', $childId) : $children->first();
            abort_unless($student, 403, 'This child is not linked to your parent account.');

            return ['scope' => 'parent_child', 'title' => $student->full_name, 'class' => $student->currentClassArm];
        }

        abort_unless($user->isTenantStaff() || $user->isSuperAdmin(), 403, 'Schedule access is unavailable for this account.');
        if ($requestedClassId) {
            $class = ClassArm::with('classLevel')->findOrFail($requestedClassId);
            $hasFullAccess = $user->isSuperAdmin() || $user->canAccessExactModule('timetable');
            $isFormTutor = (int) $class->form_tutor_id === (int) $user->id;
            abort_unless($hasFullAccess || $isFormTutor, 403, 'You can only open the timetable for your assigned form class.');

            return ['scope' => 'class', 'title' => $this->className($class), 'class' => $class];
        }

        return ['scope' => 'staff', 'title' => 'My schedule', 'class' => null];
    }

    private function periods($user, ?ClassArm $class, ?int $sessionId, string $scope): Collection
    {
        return TimetablePeriod::with(['classArm.classLevel', 'subject', 'teacher'])
            ->when($scope === 'staff', fn (Builder $query) => $query->where('teacher_id', $user->id))
            ->when($scope !== 'staff' && $class, fn (Builder $query) => $query->where('class_arm_id', $class->id))
            ->when($sessionId, fn (Builder $query) => $query->where('session_id', $sessionId))
            ->get();
    }

    private function examSchedule(ClassArm $class, ?int $termId, string $from, string $to): Collection
    {
        return ExamTimetableEntry::with(['examPeriod', 'examSession', 'subject', 'classLevel'])
            ->where('class_level_id', $class->class_level_id)
            ->whereBetween('exam_date', [$from, $to])
            ->whereHas('examPeriod', fn (Builder $query) => $query->where('status', 'published')
                ->when($termId, fn (Builder $termQuery) => $termQuery->where('term_id', $termId)))
            ->get()
            ->sortBy(fn (ExamTimetableEntry $entry) => $entry->exam_date->toDateString().'-'.str_pad((string) ($entry->examSession?->sort_order ?? 0), 4, '0', STR_PAD_LEFT))
            ->map(fn (ExamTimetableEntry $entry) => [
                'id' => $entry->id,
                'title' => $entry->examPeriod?->title ?? 'Examination',
                'date' => $entry->exam_date->toDateString(),
                'session' => $entry->examSession?->name,
                'start_time' => $this->time($entry->examSession?->start_time),
                'end_time' => $this->time($entry->examSession?->end_time),
                'subject' => $entry->subject?->name ?? 'Subject',
                'class_level' => $entry->classLevel?->name,
                'venue' => $entry->venue,
            ]);
    }

    private function duties(int $userId, ?int $termId, string $from, string $to): Collection
    {
        return ExamSupervisor::with(['entry.examPeriod', 'entry.examSession', 'entry.subject', 'entry.classLevel'])
            ->where('user_id', $userId)
            ->whereHas('entry', fn (Builder $query) => $query->whereBetween('exam_date', [$from, $to])
                ->whereHas('examPeriod', fn (Builder $periodQuery) => $periodQuery->where('status', 'published')
                    ->when($termId, fn (Builder $termQuery) => $termQuery->where('term_id', $termId))))
            ->get()
            ->sortBy(fn (ExamSupervisor $duty) => $duty->entry?->exam_date?->toDateString().'-'.str_pad((string) ($duty->entry?->examSession?->sort_order ?? 0), 4, '0', STR_PAD_LEFT))
            ->map(fn (ExamSupervisor $duty) => [
                'id' => $duty->id,
                'exam_title' => $duty->entry?->examPeriod?->title ?? 'Examination',
                'date' => $duty->entry?->exam_date?->toDateString(),
                'session' => $duty->entry?->examSession?->name,
                'start_time' => $this->time($duty->entry?->examSession?->start_time),
                'end_time' => $this->time($duty->entry?->examSession?->end_time),
                'class_level' => $duty->entry?->classLevel?->name,
                'subject' => $duty->entry?->subject?->name ?? 'Subject',
                'venue' => $duty->entry?->venue,
            ]);
    }

    private function periodPayload(TimetablePeriod $period): array
    {
        return [
            'id' => $period->id,
            'start_time' => $this->time($period->start_time),
            'end_time' => $this->time($period->end_time),
            'subject' => $period->subject?->name ?? 'Subject',
            'class' => $period->classArm ? $this->className($period->classArm) : null,
            'teacher' => $period->teacher?->name,
            'venue' => $period->venue,
        ];
    }

    private function time($value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 5);
    }

    private function className(ClassArm $class): string
    {
        return trim(($class->classLevel?->name ?? '').' '.$class->name);
    }
}
