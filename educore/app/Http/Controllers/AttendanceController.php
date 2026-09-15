<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\Term;
use App\Services\Notifications\ActivityEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $user = auth()->user();
        $isAdminTier = $user->canAccessExactModule('students') || $user->isSuperAdmin();

        $classArms = $isAdminTier
            ? ClassArm::with('classLevel')->get()
            : ClassArm::with('classLevel')->where('form_tutor_id', $user->id)->get();

        $terms = Term::with('session')->latest()->get();

        $todaySummary = AttendanceRecord::whereDate('attendance_date', today())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $weeklyTrend = AttendanceRecord::selectRaw(
                "attendance_date, COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present"
            )
            ->where('attendance_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get()
            ->map(fn ($record) => [
                'date' => \Carbon\Carbon::parse($record->attendance_date)->format('D d/m'),
                'rate' => $record->total > 0 ? round(($record->present / $record->total) * 100) : 0,
                'present' => $record->present,
                'total' => $record->total,
            ]);

        $classBreakdown = AttendanceRecord::whereDate('attendance_date', today())
            ->with('classArm.classLevel')
            ->selectRaw("class_arm_id, COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")
            ->groupBy('class_arm_id')
            ->get()
            ->map(fn ($record) => [
                'class' => optional(optional($record->classArm)->classLevel)->name.' '.optional($record->classArm)->name,
                'rate' => $record->total > 0 ? round(($record->present / $record->total) * 100) : 0,
                'present' => $record->present,
                'total' => $record->total,
            ])->sortByDesc('rate');

        return view('attendance.index', compact('weeklyTrend', 'classBreakdown', 'classArms', 'terms', 'todaySummary'));
    }

    public function sheet(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $attendanceUser = auth()->user();

        if (! $attendanceUser->canAccessExactModule('students') && ! $attendanceUser->isSuperAdmin()) {
            abort_if((int) $classArm->form_tutor_id !== (int) $attendanceUser->id, 403, 'You can only mark attendance for your assigned form class.');
        }

        $date = $request->date;
        $students = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get();

        $existing = AttendanceRecord::whereIn('student_id', $students->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $classArms = ($attendanceUser->canAccessExactModule('students') || $attendanceUser->isSuperAdmin())
            ? ClassArm::with('classLevel')->get()
            : ClassArm::with('classLevel')->where('form_tutor_id', $attendanceUser->id)->get();

        $currentTermId = Term::where('is_current', true)->value('id');

        return view('attendance.sheet', compact(
            'classArm', 'date', 'students', 'existing', 'classArms', 'currentTermId'
        ));
    }

    public function save(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'date' => ['required', 'date'],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'attendance' => ['required', 'array'],
        ]);

        $classArm = ClassArm::findOrFail($request->class_arm_id);
        $saveUser = auth()->user();

        if (! $saveUser->canAccessExactModule('students') && ! $saveUser->isSuperAdmin()) {
            abort_if((int) $classArm->form_tutor_id !== (int) $saveUser->id, 403, 'You can only save attendance for your assigned form class.');
        }

        $allowedStudentIds = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $notificationCandidates = [];

        DB::transaction(function () use ($request, $allowedStudentIds, &$notificationCandidates) {
            foreach ($request->attendance as $studentId => $status) {
                $studentId = (int) $studentId;
                abort_unless(in_array($studentId, $allowedStudentIds, true), 403);

                if (! in_array($status, ['present', 'absent', 'late', 'excused'], true)) {
                    continue;
                }

                $existing = AttendanceRecord::where('student_id', $studentId)
                    ->whereDate('attendance_date', $request->date)
                    ->first();
                $previousStatus = $existing?->status;

                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'attendance_date' => $request->date,
                    ],
                    [
                        'class_arm_id' => $request->class_arm_id,
                        'term_id' => $request->term_id,
                        'status' => $status,
                        'marked_by' => Auth::id(),
                    ]
                );

                if (in_array($status, ['absent', 'late'], true)
                    && ! in_array($previousStatus, ['absent', 'late'], true)) {
                    $notificationCandidates[] = [
                        'student_id' => $studentId,
                        'status' => $status,
                    ];
                }
            }
        });

        if ($notificationCandidates !== []) {
            $students = Student::with(['guardians', 'tenant'])
                ->whereIn('id', collect($notificationCandidates)->pluck('student_id')->unique())
                ->get()
                ->keyBy('id');
            $emailService = app(ActivityEmailService::class);

            foreach ($notificationCandidates as $candidate) {
                $student = $students->get($candidate['student_id']);
                if ($student) {
                    $emailService->notifyAttendanceStatus($student, $candidate['status'], $request->date);
                }
            }
        }

        return back()->with('success', 'Attendance saved for '.date('d M Y', strtotime($request->date)).'.');
    }

    public function report(Request $request)
    {
        $reportUser = auth()->user();
        $isAdminTier = $reportUser->canAccessExactModule('students') || $reportUser->isSuperAdmin();
        $classArms = $isAdminTier
            ? ClassArm::with('classLevel')->get()
            : ClassArm::with('classLevel')->where('form_tutor_id', $reportUser->id)->get();
        $terms = Term::with('session')->latest()->get();

        if (! $request->filled('class_arm_id') || ! $request->filled('term_id')) {
            return view('attendance.report', compact('classArms', 'terms'));
        }

        $request->validate([
            'class_arm_id' => [Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'term_id' => [Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);

        if (! $isAdminTier) {
            abort_if($classArm->form_tutor_id !== $reportUser->id, 403, 'You can only view the attendance report for your assigned form class.');
        }

        $term = Term::findOrFail($request->term_id);
        $students = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get();

        $records = AttendanceRecord::whereIn('student_id', $students->pluck('id'))
            ->where('term_id', $term->id)
            ->get();

        $schoolDays = AttendanceRecord::where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->distinct('attendance_date')
            ->count('attendance_date');

        $summary = $students->map(function ($student) use ($records, $schoolDays) {
            $studentRecords = $records->where('student_id', $student->id);
            $present = $studentRecords->whereIn('status', ['present', 'late'])->count();
            $absent = $studentRecords->where('status', 'absent')->count();
            $late = $studentRecords->where('status', 'late')->count();
            $excused = $studentRecords->where('status', 'excused')->count();
            $rate = $schoolDays > 0 ? round(($present / $schoolDays) * 100) : 0;

            return [
                'student' => $student,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'school_days' => $schoolDays,
                'rate' => $rate,
            ];
        })->sortByDesc('rate');

        return view('attendance.report', compact(
            'classArms', 'terms', 'classArm', 'term', 'summary', 'schoolDays'
        ));
    }

    public function studentHistory(Request $request, Student $student)
    {
        $terms = Term::with('session')->latest()->get();
        $termId = $request->term_id ?? Term::where('is_current', true)->value('id');

        $records = AttendanceRecord::where('student_id', $student->id)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->orderBy('attendance_date', 'desc')
            ->get();

        $stats = [
            'present' => $records->whereIn('status', ['present', 'late'])->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'total' => $records->count(),
        ];

        $stats['rate'] = $stats['total'] > 0
            ? round(($stats['present'] / $stats['total']) * 100)
            : 0;

        return view('attendance.student', compact('student', 'records', 'stats', 'terms', 'termId'));
    }
}
