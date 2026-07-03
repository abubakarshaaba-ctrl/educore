<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentClassTransferController extends Controller
{
    private const PERMISSION_VIEW = 'student.transfer.view';
    private const PERMISSION_REQUEST = 'student.transfer.request';
    private const PERMISSION_APPROVE = 'student.transfer.approve';
    private const PERMISSION_REJECT = 'student.transfer.reject';
    private const PERMISSION_CANCEL = 'student.transfer.cancel';

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeTransfer(self::PERMISSION_VIEW);

        $tenantId = $this->tenantId();
        $baseQuery = StudentClassTransfer::where('tenant_id', $tenantId);

        $transfers = (clone $baseQuery)
            ->with([
                'student.currentClassArm.classLevel',
                'fromClassArm.classLevel',
                'toClassArm.classLevel',
                'academicSession',
                'term',
                'requestedBy',
                'approvedBy',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('class_arm_id'), function ($q) use ($request) {
                $classArmId = (int) $request->input('class_arm_id');
                $q->where(function ($inner) use ($classArmId) {
                    $inner->where('from_class_arm_id', $classArmId)
                        ->orWhere('to_class_arm_id', $classArmId);
                });
            })
            ->when($request->filled('academic_session_id'), fn ($q) => $q->where('academic_session_id', $request->integer('academic_session_id')))
            ->when($request->filled('term_id'), fn ($q) => $q->where('term_id', $request->integer('term_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . trim($request->input('search')) . '%';
                $q->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('admission_number', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$search])
                        ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) like ?", [$search]);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = collect(StudentClassTransfer::STATUSES)
            ->mapWithKeys(fn ($status) => [
                $status => (clone $baseQuery)->where('status', $status)->count(),
            ]);

        return view('students.class-transfers.index', [
            'transfers' => $transfers,
            'summary' => $summary,
            'statuses' => StudentClassTransfer::STATUSES,
            'classArms' => $this->classArmOptions($tenantId),
            'sessions' => $this->sessionOptions($tenantId),
            'terms' => $this->termOptions($tenantId),
            'canRequest' => $this->canTransfer(auth()->user(), self::PERMISSION_REQUEST),
            'filters' => $request->only(['search', 'status', 'class_arm_id', 'academic_session_id', 'term_id']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeTransfer(self::PERMISSION_REQUEST);

        $tenantId = $this->tenantId();

        return view('students.class-transfers.create', [
            'students' => Student::with('currentClassArm.classLevel')
                ->where('tenant_id', $tenantId)
                ->where('status', Student::STATUS_ACTIVE)
                ->whereNotNull('current_class_arm_id')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'classArms' => $this->classArmOptions($tenantId),
            'activeContext' => $this->activeAcademicContextOrNull($tenantId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTransfer(self::PERMISSION_REQUEST);

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q
                    ->where('tenant_id', $tenantId)
                    ->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at')),
            ],
            'to_class_arm_id' => [
                'required',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $activeContext = $this->activeAcademicContext($tenantId);
        $user = auth()->user();

        $transfer = DB::transaction(function () use ($request, $tenantId, $data, $activeContext, $user) {
            $student = Student::where('tenant_id', $tenantId)
                ->whereKey($data['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$student->isActive()) {
                throw ValidationException::withMessages(['student_id' => 'Only active students can be transferred between classes.']);
            }

            $pendingExists = StudentClassTransfer::where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('status', StudentClassTransfer::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($pendingExists) {
                throw ValidationException::withMessages(['student_id' => 'This student already has a pending interclass transfer request.']);
            }

            $currentEnrollment = $this->currentEnrollmentForUpdate($tenantId, $student);
            $destination = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($data['to_class_arm_id'])
                ->firstOrFail();

            if ((int) $currentEnrollment->class_arm_id === (int) $destination->id) {
                throw ValidationException::withMessages(['to_class_arm_id' => 'Destination class arm must be different from the current class arm.']);
            }

            $documentPath = $this->storeSupportingDocument($request, $tenantId);

            $transfer = StudentClassTransfer::create([
                'tenant_id' => $tenantId,
                'student_id' => $student->id,
                'academic_session_id' => $activeContext['session']->id,
                'term_id' => $activeContext['term']->id,
                'from_class_arm_id' => $currentEnrollment->class_arm_id,
                'to_class_arm_id' => $destination->id,
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'status' => StudentClassTransfer::STATUS_PENDING,
                'requested_by' => $user->id,
                'supporting_document' => $documentPath,
            ]);

            $this->auditLogger->record(
                $tenantId,
                $user,
                $transfer,
                'student_class_transfer.requested',
                [],
                [
                    'student_id' => $student->id,
                    'from_class_arm_id' => $currentEnrollment->class_arm_id,
                    'to_class_arm_id' => $destination->id,
                    'academic_session_id' => $activeContext['session']->id,
                    'term_id' => $activeContext['term']->id,
                    'effective_date' => $data['effective_date'],
                    'status' => StudentClassTransfer::STATUS_PENDING,
                ],
                $data['reason'],
                $request
            );

            return $transfer;
        });

        return redirect()
            ->route('students.class-transfers.show', $transfer)
            ->with('success', 'Interclass transfer request created.');
    }

    public function show(StudentClassTransfer $classTransfer): View
    {
        $this->authorizeTransfer(self::PERMISSION_VIEW);

        $transfer = $this->tenantTransfer($classTransfer)
            ->load([
                'student.user',
                'student.currentClassArm.classLevel',
                'fromClassArm.classLevel',
                'toClassArm.classLevel',
                'academicSession',
                'term',
                'requestedBy',
                'approvedBy',
                'rejectedBy',
                'cancelledBy',
            ]);

        $audits = AuditLog::with('actor')
            ->where('auditable_type', StudentClassTransfer::class)
            ->where('auditable_id', $transfer->id)
            ->latest()
            ->get();

        return view('students.class-transfers.show', [
            'transfer' => $transfer,
            'audits' => $audits,
            'canApprove' => $this->canTransfer(auth()->user(), self::PERMISSION_APPROVE, $transfer),
            'canReject' => $this->canTransfer(auth()->user(), self::PERMISSION_REJECT, $transfer),
            'canCancel' => $this->canTransfer(auth()->user(), self::PERMISSION_CANCEL, $transfer),
        ]);
    }

    public function approve(Request $request, StudentClassTransfer $classTransfer): RedirectResponse
    {
        $this->authorizeTransfer(self::PERMISSION_APPROVE);

        $tenantId = $this->tenantId();
        $user = auth()->user();

        $transfer = DB::transaction(function () use ($request, $classTransfer, $tenantId, $user) {
            $transferSnapshot = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($classTransfer->id)
                ->firstOrFail();

            $student = Student::where('tenant_id', $tenantId)
                ->whereKey($transferSnapshot->student_id)
                ->lockForUpdate()
                ->firstOrFail();

            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($classTransfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages(['transfer' => 'Only pending transfer requests can be approved.']);
            }

            if (!$student->isActive()) {
                throw ValidationException::withMessages(['transfer' => 'The student is no longer active.']);
            }

            $currentEnrollment = $this->currentEnrollmentForUpdate($tenantId, $student);

            if ((int) $currentEnrollment->class_arm_id !== (int) $transfer->from_class_arm_id) {
                throw ValidationException::withMessages(['transfer' => 'The student current enrollment no longer matches the transfer source class.']);
            }

            if ((int) $student->current_class_arm_id !== (int) $transfer->from_class_arm_id) {
                throw ValidationException::withMessages(['transfer' => 'The student current class no longer matches the transfer source class.']);
            }

            $destination = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($transfer->to_class_arm_id)
                ->firstOrFail();

            if ((int) $transfer->from_class_arm_id === (int) $destination->id) {
                throw ValidationException::withMessages(['transfer' => 'Source and destination class arms must be different.']);
            }

            $this->assertDestinationHasCapacity($tenantId, $destination);

            $currentEnrollment->forceFill([
                'is_current' => false,
                'end_date' => $transfer->effective_date,
                'status' => StudentEnrollment::STATUS_TRANSFERRED,
                'ended_by' => $user->id,
                'ended_reason' => 'Interclass transfer #' . $transfer->id,
            ])->save();

            $newEnrollment = StudentEnrollment::create([
                'tenant_id' => $tenantId,
                'student_id' => $student->id,
                'class_arm_id' => $destination->id,
                'session_id' => $transfer->academic_session_id,
                'term_id' => $transfer->term_id,
                'start_date' => $transfer->effective_date,
                'end_date' => null,
                'is_current' => true,
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'created_by' => $user->id,
            ]);

            $oldClassArmId = $student->current_class_arm_id;
            $student->forceFill(['current_class_arm_id' => $destination->id])->save();
            $syncedSubjects = $student->syncCompulsorySubjects($transfer->academic_session_id);

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_COMPLETED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $user,
                $transfer,
                'student_class_transfer.completed',
                [
                    'student_id' => $student->id,
                    'class_arm_id' => $oldClassArmId,
                    'current_enrollment_id' => $currentEnrollment->id,
                    'transfer_status' => StudentClassTransfer::STATUS_PENDING,
                ],
                [
                    'student_id' => $student->id,
                    'class_arm_id' => $destination->id,
                    'new_enrollment_id' => $newEnrollment->id,
                    'transfer_status' => StudentClassTransfer::STATUS_COMPLETED,
                    'synced_subjects' => $syncedSubjects,
                ],
                $transfer->reason,
                $request
            );

            return $transfer;
        });

        return redirect()
            ->route('students.class-transfers.show', $transfer)
            ->with('success', 'Interclass transfer approved and completed.');
    }

    public function reject(Request $request, StudentClassTransfer $classTransfer): RedirectResponse
    {
        $this->authorizeTransfer(self::PERMISSION_REJECT);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $tenantId = $this->tenantId();
        $user = auth()->user();

        $transfer = DB::transaction(function () use ($request, $classTransfer, $tenantId, $user, $data) {
            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($classTransfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages(['transfer' => 'Only pending transfer requests can be rejected.']);
            }

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_REJECTED,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $user,
                $transfer,
                'student_class_transfer.rejected',
                ['status' => StudentClassTransfer::STATUS_PENDING],
                ['status' => StudentClassTransfer::STATUS_REJECTED],
                $data['rejection_reason'],
                $request
            );

            return $transfer;
        });

        return redirect()
            ->route('students.class-transfers.show', $transfer)
            ->with('success', 'Interclass transfer request rejected.');
    }

    public function cancel(Request $request, StudentClassTransfer $classTransfer): RedirectResponse
    {
        $transferForAuth = $this->tenantTransfer($classTransfer);
        $this->authorizeTransfer(self::PERMISSION_CANCEL, $transferForAuth);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);

        $tenantId = $this->tenantId();
        $user = auth()->user();

        $transfer = DB::transaction(function () use ($request, $classTransfer, $tenantId, $user, $data) {
            $transfer = StudentClassTransfer::where('tenant_id', $tenantId)
                ->whereKey($classTransfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StudentClassTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages(['transfer' => 'Only pending transfer requests can be cancelled.']);
            }

            if (!$this->canTransfer($user, self::PERMISSION_CANCEL, $transfer)) {
                abort(403);
            }

            $transfer->forceFill([
                'status' => StudentClassTransfer::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'],
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $user,
                $transfer,
                'student_class_transfer.cancelled',
                ['status' => StudentClassTransfer::STATUS_PENDING],
                ['status' => StudentClassTransfer::STATUS_CANCELLED],
                $data['cancellation_reason'],
                $request
            );

            return $transfer;
        });

        return redirect()
            ->route('students.class-transfers.show', $transfer)
            ->with('success', 'Interclass transfer request cancelled.');
    }

    private function currentEnrollmentForUpdate(int $tenantId, Student $student): StudentEnrollment
    {
        $currentEnrollments = StudentEnrollment::where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->get();

        if ($currentEnrollments->count() !== 1) {
            throw ValidationException::withMessages(['student_id' => 'Student must have exactly one current enrollment before transfer.']);
        }

        $currentEnrollment = $currentEnrollments->first();

        if ((int) $currentEnrollment->class_arm_id !== (int) $student->current_class_arm_id) {
            throw ValidationException::withMessages(['student_id' => 'Student current enrollment does not match the current class arm.']);
        }

        return $currentEnrollment;
    }

    private function activeAcademicContext(int $tenantId): array
    {
        $context = $this->activeAcademicContextOrNull($tenantId);

        if (!$context) {
            throw ValidationException::withMessages([
                'student_id' => 'Exactly one active academic session and active term must exist before requesting a transfer.',
            ]);
        }

        return $context;
    }

    private function activeAcademicContextOrNull(int $tenantId): ?array
    {
        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->get();

        if ($sessions->count() !== 1) {
            return null;
        }

        $terms = Term::where('tenant_id', $tenantId)
            ->where('session_id', $sessions->first()->id)
            ->where('is_current', true)
            ->get();

        if ($terms->count() !== 1) {
            return null;
        }

        return [
            'session' => $sessions->first(),
            'term' => $terms->first(),
        ];
    }

    private function assertDestinationHasCapacity(int $tenantId, ClassArm $destination): void
    {
        if (!Schema::hasColumn('class_arms', 'capacity') || empty($destination->capacity)) {
            return;
        }

        $activeCount = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $destination->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->count();

        if ($activeCount >= (int) $destination->capacity) {
            throw ValidationException::withMessages(['transfer' => 'Destination class arm has reached its configured capacity.']);
        }
    }

    private function storeSupportingDocument(Request $request, int $tenantId): ?string
    {
        if (!$request->hasFile('supporting_document')) {
            return null;
        }

        return $request->file('supporting_document')
            ->store('student-class-transfers/' . $tenantId);
    }

    public function downloadDocument(StudentClassTransfer $classTransfer)
    {
        $this->authorizeTransfer(self::PERMISSION_VIEW);

        $transfer = $this->tenantTransfer($classTransfer);

        abort_unless($transfer->supporting_document, 404);

        if (Storage::exists($transfer->supporting_document)) {
            return Storage::download($transfer->supporting_document);
        }

        if (Storage::disk('public')->exists($transfer->supporting_document)) {
            return Storage::disk('public')->download($transfer->supporting_document);
        }

        abort(404);
    }

    private function tenantTransfer(StudentClassTransfer $classTransfer): StudentClassTransfer
    {
        return StudentClassTransfer::where('tenant_id', $this->tenantId())
            ->whereKey($classTransfer->id)
            ->firstOrFail();
    }

    private function classArmOptions(int $tenantId)
    {
        return ClassArm::with('classLevel')
            ->where('tenant_id', $tenantId)
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
    }

    private function sessionOptions(int $tenantId)
    {
        return AcademicSession::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();
    }

    private function termOptions(int $tenantId)
    {
        return Term::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_unless($tenantId, 403, 'A tenant context is required for interclass transfers.');

        return (int) $tenantId;
    }

    private function authorizeTransfer(string $permission, ?StudentClassTransfer $transfer = null): void
    {
        abort_unless($this->canTransfer(auth()->user(), $permission, $transfer), 403);
    }

    private function canTransfer(?User $user, string $permission, ?StudentClassTransfer $transfer = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can($permission);
    }
}
