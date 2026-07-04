<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\Student;
use App\Services\LifecycleAuditLogger;
use App\Services\StudentLifecycle\StudentLifecycleRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentArchiveController extends Controller
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeArchive('student.archive.view');
        $filters = $this->archiveFilters($request);

        $students = $this->archiveQuery($filters)
            ->with(['currentClassArm.classLevel'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        $summary = collect(Student::ARCHIVE_STATUSES)
            ->mapWithKeys(fn ($status) => [
                $status => Student::where('tenant_id', $this->tenantId())->where('status', $status)->count(),
            ]);

        return view('students.archive.index', [
            'students' => $students,
            'summary' => $summary,
            'statusLabels' => StudentLifecycleRules::statusLabels(),
            'classArms' => ClassArm::with('classLevel')
                ->where('tenant_id', $this->tenantId())
                ->orderBy('class_level_id')
                ->orderBy('name')
                ->get(),
            'sessions' => AcademicSession::where('tenant_id', $this->tenantId())
                ->orderByDesc('is_current')
                ->orderByDesc('id')
                ->get(),
            'filters' => $filters,
            'canExport' => auth()->user()?->can('student.archive.export'),
        ]);
    }

    public function show(Student $student): View
    {
        $this->authorizeArchive('student.archive.view');

        $student = Student::where('tenant_id', $this->tenantId())
            ->whereKey($student->id)
            ->whereIn('status', Student::ARCHIVE_STATUSES)
            ->firstOrFail();

        $student->load([
            'currentClassArm.classLevel',
            'guardians',
            'statusHistories.changedBy',
            'statusHistories.approvedBy',
            'enrolmentHistory.classArm.classLevel',
            'enrolmentHistory.session',
            'enrolmentHistory.term',
            'invoices' => fn ($q) => $q->latest()->limit(10),
            'cbtSessions.exam',
        ]);

        $audits = AuditLog::with('actor')
            ->where('tenant_id', $this->tenantId())
            ->where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->latest()
            ->get();

        $exitHistory = $student->statusHistories()
            ->where('new_status', $student->status)
            ->latest('effective_date')
            ->first();

        return view('students.archive.show', [
            'student' => $student,
            'statusLabels' => StudentLifecycleRules::statusLabels(),
            'exitHistory' => $exitHistory,
            'audits' => $audits,
            'canReactivate' => auth()->user()?->can('student.reactivate') && StudentLifecycleRules::canReactivate($student->status),
            'canReadmit' => auth()->user()?->can('student.readmit') && $student->status === Student::STATUS_TRANSFERRED_OUT,
            'canCorrectGraduation' => auth()->user()?->can('student.status.correct-graduation') && $student->status === Student::STATUS_GRADUATED,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizeArchive('student.archive.export');
        $filters = $this->archiveFilters($request);

        $students = $this->archiveQuery($filters)
            ->with(['currentClassArm.classLevel'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $this->auditLogger->record(
            $this->tenantId(),
            auth()->user(),
            auth()->user(),
            'student.archive.exported',
            [],
            ['filters' => $filters, 'count' => $students->count()],
            'Student archive export generated.',
            $request
        );

        $filename = 'student_archive_' . now()->format('Y_m_d_His') . '.csv';

        return Response::streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Admission No', 'Name', 'Status', 'Last Known Class', 'Admission Date', 'Graduation Date']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->admission_number,
                    $student->full_name,
                    $student->status_label,
                    trim(($student->currentClassArm?->classLevel?->name ?? '') . ' ' . ($student->currentClassArm?->name ?? '')),
                    optional($student->admission_date)->toDateString(),
                    optional($student->graduation_date)->toDateString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function archiveQuery(array $filters)
    {
        return Student::where('tenant_id', $this->tenantId())
            ->whereIn('status', Student::ARCHIVE_STATUSES)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = '%' . trim($filters['search']) . '%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('middle_name', 'like', $search)
                        ->orWhere('admission_number', 'like', $search)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$search])
                        ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) like ?", [$search]);
                });
            })
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['class_arm_id']), fn ($query) => $query->where('current_class_arm_id', (int) $filters['class_arm_id']))
            ->when(!empty($filters['exit_from']), function ($query) use ($filters) {
                $query->whereHas('statusHistories', fn ($history) => $history
                    ->whereColumn('new_status', 'students.status')
                    ->whereDate('effective_date', '>=', $filters['exit_from']));
            })
            ->when(!empty($filters['exit_to']), function ($query) use ($filters) {
                $query->whereHas('statusHistories', fn ($history) => $history
                    ->whereColumn('new_status', 'students.status')
                    ->whereDate('effective_date', '<=', $filters['exit_to']));
            })
            ->when(!empty($filters['session_id']), function ($query) use ($filters) {
                $query->whereHas('enrolmentHistory', fn ($enrollment) => $enrollment->where('session_id', (int) $filters['session_id']));
            });
    }

    private function archiveFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Student::ARCHIVE_STATUSES)],
            'class_arm_id' => [
                'nullable',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())),
            ],
            'session_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_sessions', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())),
            ],
            'exit_from' => ['nullable', 'date'],
            'exit_to' => ['nullable', 'date', 'after_or_equal:exit_from'],
        ]);
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_unless($tenantId, 403, 'A tenant context is required.');

        return (int) $tenantId;
    }

    private function authorizeArchive(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
