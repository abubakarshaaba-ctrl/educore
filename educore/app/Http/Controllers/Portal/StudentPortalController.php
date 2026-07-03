<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\AttendanceRecord;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Models\Announcement;
use App\Models\TimetablePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * StudentPortalController
 *
 * Handles the student-facing portal. Accessed by users with role = 'student'.
 * All data is scoped to the logged-in student's record via user_id.
 */
class StudentPortalController extends Controller
{
    private function getStudent(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();

        if (!$student) {
            abort(403, 'No student profile linked to your account.');
        }

        return $student;
    }

    /**
     * Apply CBT exam class filtering only when the relevant columns exist.
     * This prevents dashboard failure when cbt_exams does not yet have
     * class_level_id or class_arm_id.
     */
    private function applyCbtScope($query, Student $student)
    {
        $levelId = $student->currentClassArm?->class_level_id;
        $armId   = $student->currentClassArm?->id;

        if (Schema::hasColumn('cbt_exams', 'class_level_id') && $levelId) {
            return $query->where(function ($q) use ($levelId) {
                $q->whereNull('class_level_id')
                  ->orWhere('class_level_id', $levelId);
            });
        }

        if (Schema::hasColumn('cbt_exams', 'class_arm_id') && $armId) {
            return $query->where(function ($q) use ($armId) {
                $q->whereNull('class_arm_id')
                  ->orWhere('class_arm_id', $armId);
            });
        }

        return $query;
    }

    /**
     * Prefer scheduled exam date columns when available.
     */
    private function cbtOrderColumn(): string
    {
        foreach (['scheduled_start', 'created_at'] as $column) {
            if (Schema::hasColumn('cbt_exams', $column)) {
                return $column;
            }
        }

        return 'created_at';
    }

    /**
     * Use the correct attendance date column based on the installed schema.
     */
    private function attendanceOrderColumn(): string
    {
        foreach (['date', 'attendance_date', 'recorded_at', 'created_at'] as $column) {
            if (Schema::hasColumn('attendance_records', $column)) {
                return $column;
            }
        }

        return 'created_at';
    }

    /**
     * Use publish_date when available, otherwise fall back to created_at.
     */
    private function announcementOrderColumn(): string
    {
        return Schema::hasColumn('announcements', 'publish_date') ? 'publish_date' : 'created_at';
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        $student     = $this->getStudent();
        $currentTerm = Term::where('is_current', true)->first();

        $student->load(['currentClassArm.classLevel', 'currentClassArm.academicTrack']);

        // Current term summary
        $summary = $currentTerm
            ? TermlySummary::where('student_id', $student->id)
                ->where('term_id', $currentTerm->id)
                ->first()
            : null;

        // Attendance this term
        $attendance = $currentTerm
            ? AttendanceRecord::where('student_id', $student->id)
                ->where('term_id', $currentTerm->id)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")
                ->first()
            : null;

        // Upcoming CBT exams
        $upcomingExams = collect();
        if ($student->status === Student::STATUS_ACTIVE) {
            $upcomingExamsQuery = CbtExam::where('tenant_id', $student->tenant_id)
                ->with('questionBank.subject')
                ->where('status', 'published');

            $upcomingExamsQuery = $this->applyCbtScope($upcomingExamsQuery, $student);

            $upcomingExams = $upcomingExamsQuery
                ->orderBy($this->cbtOrderColumn())
                ->limit(5)
                ->get();
        }

        // Recent CBT results
        $recentResults = CbtStudentSession::where('student_id', $student->id)
            ->where('status', 'graded')
            ->with('exam.questionBank.subject')
            ->latest()
            ->limit(5)
            ->get();

        // Announcements
        $announcements = Announcement::where('tenant_id', $student->tenant_id)
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'students'])
            ->latest($this->announcementOrderColumn())
            ->limit(5)
            ->get();

        return view('portal.student.dashboard', compact(
            'student',
            'currentTerm',
            'summary',
            'attendance',
            'upcomingExams',
            'recentResults',
            'announcements'
        ));
    }

    // ── Report Cards ──────────────────────────────────────────────────
    public function results(Request $request)
    {
        $student = $this->getStudent();
        $terms   = Term::with('session')->latest()->get();
        $termId  = $request->get('term_id', optional($terms->firstWhere('is_current', true))->id);

        $summary = $termId
            ? TermlySummary::where('student_id', $student->id)
                ->where('term_id', $termId)
                ->first()
            : null;

        // Avoid dependency on possibly misspelled Term relationship names.
        $availableTermIds = TermlySummary::where('student_id', $student->id)
            ->pluck('term_id')
            ->unique()
            ->values();

        $availableTerms = Term::whereIn('id', $availableTermIds)
            ->with('session')
            ->latest()
            ->get();

        return view('portal.student.results', compact(
            'student',
            'terms',
            'availableTerms',
            'termId',
            'summary'
        ));
    }

    // ── Timetable ─────────────────────────────────────────────────────
    public function timetable()
    {
        $student = $this->getStudent();
        $arm     = $student->currentClassArm;

        $timetable = collect();

        if ($student->status === Student::STATUS_ACTIVE && $arm && Schema::hasTable('timetable_periods')) {
            $currentSession = AcademicSession::where('is_current', true)->first();

            $periods = TimetablePeriod::where('class_arm_id', $arm->id)
                ->when($currentSession, fn ($query) => $query->where('session_id', $currentSession->id))
                ->with(['subject', 'teacher'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            $timetable = $periods
                ->groupBy(fn ($period) => ucfirst($period->day_of_week))
                ->map(function ($dayPeriods) {
                    return $dayPeriods->sortBy('start_time')
                        ->values()
                        ->each(function ($period, int $index): void {
                            $period->period_number = $index + 1;
                        });
                });
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('portal.student.timetable', compact('student', 'arm', 'timetable', 'days'));
    }

    // ── Attendance ────────────────────────────────────────────────────
    public function attendance(Request $request)
    {
        $student     = $this->getStudent();
        $currentTerm = Term::where('is_current', true)->first();
        $termId      = $request->get('term_id', $currentTerm?->id);
        $terms       = Term::with('session')->latest()->get();

        $records = AttendanceRecord::where('student_id', $student->id)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->orderByDesc($this->attendanceOrderColumn())
            ->get();

        $stats = [
            'total'   => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent'  => $records->where('status', 'absent')->count(),
            'late'    => $records->where('status', 'late')->count(),
        ];

        $stats['rate'] = $stats['total'] > 0
            ? round(($stats['present'] / $stats['total']) * 100, 1)
            : 0;

        return view('portal.student.attendance', compact('student', 'records', 'stats', 'terms', 'termId'));
    }

    // ── CBT Exams ─────────────────────────────────────────────────────
    public function exams()
    {
        $student = $this->getStudent();

        $exams = collect();
        if ($student->status === Student::STATUS_ACTIVE) {
            $examsQuery = CbtExam::where('tenant_id', $student->tenant_id)
                ->with('questionBank.subject')
                ->where('status', 'published');

            $examsQuery = $this->applyCbtScope($examsQuery, $student);

            $exams = $examsQuery
                ->orderByDesc($this->cbtOrderColumn())
                ->get();
        }

        // Student sessions for each exam
        $sessions = CbtStudentSession::where('student_id', $student->id)
            ->whereIn('cbt_exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('cbt_exam_id');

        return view('portal.student.exams', compact('student', 'exams', 'sessions'));
    }

    // ── Subjects ─────────────────────────────────────────────────────
    public function subjects()
    {
        $student  = $this->getStudent();
        $session  = \App\Models\AcademicSession::where('is_current', true)->first();
        $subjects = $student->status === Student::STATUS_ACTIVE && method_exists($student, 'currentEligibleSubjects')
            ? $student->currentEligibleSubjects($session?->id)
            : collect();

        return view('portal.student.subjects', compact('student', 'subjects', 'session'));
    }
}
