<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\TimetablePeriod;
use App\Services\MobileReportCardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    public function dashboard(Request $request)
    {
        $student = $this->student($request);
        $student->load(['currentClassArm.classLevel', 'currentClassArm.academicTrack']);
        $term = Term::where('is_current', true)->first();

        $summary = $term
            ? TermlySummary::where('student_id', $student->id)->where('term_id', $term->id)->first()
            : null;

        $attendance = $term
            ? AttendanceRecord::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->get(['status'])
            : collect();
        $present = $attendance->where('status', 'present')->count();
        $attendanceRate = $attendance->isNotEmpty()
            ? round(($present / $attendance->count()) * 100, 1)
            : 0;

        $upcoming = collect();
        if ($student->status === Student::STATUS_ACTIVE) {
            $query = CbtExam::with('questionBank.subject')->where('status', 'published');
            $upcoming = $this->scopeExams($query, $student)
                ->orderBy('scheduled_start')
                ->limit(5)
                ->get();
        }

        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', ['all', 'students'])
            ->where(fn ($query) => $query->whereNull('expire_date')->orWhere('expire_date', '>=', now()->toDateString()))
            ->latest(Schema::hasColumn('announcements', 'publish_date') ? 'publish_date' : 'created_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'priority', 'publish_date']);

        return response()->json([
            'student' => $this->studentPayload($student),
            'current_term' => $term?->only(['id', 'name']),
            'summary' => $summary ? [
                'average' => $summary->final_average,
                'position' => $summary->position_in_class,
                'subjects_offered' => $summary->subjects_offered,
                'subjects_failed' => $summary->subjects_failed,
            ] : null,
            'attendance' => [
                'total' => $attendance->count(),
                'present' => $present,
                'rate' => $attendanceRate,
            ],
            'upcoming_exams' => $upcoming->map(fn (CbtExam $exam) => $this->examPayload($exam)),
            'announcements' => $announcements,
        ]);
    }

    public function timetable(Request $request)
    {
        $student = $this->student($request);
        $session = AcademicSession::where('is_current', true)->first();
        $periods = collect();

        if ($student->status === Student::STATUS_ACTIVE && $student->current_class_arm_id) {
            $periods = TimetablePeriod::with(['subject', 'teacher'])
                ->where('class_arm_id', $student->current_class_arm_id)
                ->when($session, fn ($query) => $query->where('session_id', $session->id))
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn (TimetablePeriod $period) => [
                    'id' => $period->id,
                    'day' => ucfirst($period->day_of_week),
                    'start_time' => $period->start_time,
                    'end_time' => $period->end_time,
                    'subject' => $period->subject?->name,
                    'teacher' => $period->teacher?->name,
                    'venue' => $period->venue,
                ]);
        }

        return response()->json([
            'student' => $this->studentPayload($student->loadMissing('currentClassArm.classLevel')),
            'periods' => $periods,
        ]);
    }

    public function results(Request $request, MobileReportCardService $reports)
    {
        $student = $this->student($request);
        return response()->json([
            'student' => $this->studentPayload($student->loadMissing('currentClassArm.classLevel')),
            'results' => $reports->forStudent($student),
        ]);
    }

    public function attendance(Request $request)
    {
        $student = $this->student($request);
        $terms = Term::with('session')->latest('start_date')->get();
        $term = $request->filled('term_id')
            ? $terms->firstWhere('id', $request->integer('term_id'))
            : ($terms->firstWhere('is_current', true) ?? $terms->first());

        if ($request->filled('term_id')) {
            abort_unless($term, 422, 'The selected term is not available for this school.');
        }

        $records = AttendanceRecord::where('student_id', $student->id)
            ->when($term, fn ($query) => $query->where('term_id', $term->id))
            ->orderByDesc('attendance_date')
            ->limit(150)
            ->get(['attendance_date', 'status', 'remark']);

        return response()->json([
            'student' => $this->studentPayload($student->loadMissing('currentClassArm.classLevel')),
            'terms' => $terms->map(fn (Term $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'session' => $item->session?->name,
            ])->values(),
            'selected_term_id' => $term?->id,
            'records' => $records,
            'stats' => [
                'total' => $records->count(),
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'rate' => $records->isNotEmpty()
                    ? round(($records->whereIn('status', ['present', 'late'])->count() / $records->count()) * 100, 1)
                    : 0,
            ],
        ]);
    }

    public function exams(Request $request)
    {
        $student = $this->student($request);
        $exams = collect();

        if ($student->status === Student::STATUS_ACTIVE) {
            $exams = $this->scopeExams(
                CbtExam::with('questionBank.subject')->where('status', 'published'),
                $student
            )->orderByDesc('scheduled_start')->get();
        }

        $sessions = CbtStudentSession::where('student_id', $student->id)
            ->whereIn('cbt_exam_id', $exams->pluck('id'))
            ->orderByDesc('attempt_number')->get()
            ->groupBy('cbt_exam_id')->map->first();

        return response()->json([
            'exams' => $exams->map(function (CbtExam $exam) use ($sessions) {
                $payload = $this->examPayload($exam);
                $session = $sessions->get($exam->id);
                $payload['attempt'] = $session ? [
                    'status' => $session->status,
                    'score' => $session->raw_score ?? $session->score,
                    'maximum_score' => $session->maximum_score,
                    'attempt_number' => $session->attempt_number,
                    'is_active_result' => $session->is_active_result,
                    'submitted_at' => $session->submitted_at,
                ] : null;
                return $payload;
            }),
        ]);
    }

    private function student(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user?->isStudent(), 403, 'Student portal access only.');

        $student = Student::where('user_id', $user->id)->first();
        abort_unless($student, 403, 'No student profile is linked to this account.');
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 403, 'Student profile is outside your school.');

        return $student;
    }

    private function scopeExams(Builder $query, Student $student): Builder
    {
        if (Schema::hasColumn('cbt_exams', 'class_arm_id') && $student->current_class_arm_id) {
            $armId = $student->current_class_arm_id;
            $query->where(fn ($inner) => $inner->whereNull('class_arm_id')->orWhere('class_arm_id', $armId)->orWhereHas('classArms', fn ($assigned) => $assigned->where('class_arms.id', $armId)));
        }
        return $query;
    }

    private function studentPayload(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'status' => $student->status,
            'class' => $student->currentClassArm ? [
                'id' => $student->currentClassArm->id,
                'name' => trim(($student->currentClassArm->classLevel?->name ?? '') . ' ' . $student->currentClassArm->name),
            ] : null,
        ];
    }

    private function examPayload(CbtExam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'subject' => $exam->questionBank?->subject?->name,
            'duration_minutes' => $exam->duration_minutes,
            'scheduled_start' => $exam->scheduled_start,
            'scheduled_end' => $exam->scheduled_end,
            'status' => $exam->status,
            'sections' => $exam->sections()->where('is_active', true)->orderBy('display_order')->get()->map(fn ($section) => [
                'id' => $section->id, 'code' => $section->code, 'name' => $section->name,
                'answer_mode' => $section->answer_mode, 'max_marks' => $section->max_marks,
            ])->values(),
        ];
    }
}
