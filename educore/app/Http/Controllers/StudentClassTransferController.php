<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\Term;
use App\Models\User;
use App\Services\StudentClassTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentClassTransferController extends Controller
{
    private const PERMISSION_VIEW = 'student.transfer.view';
    private const PERMISSION_REQUEST = 'student.transfer.request';
    private const PERMISSION_APPROVE = 'student.transfer.approve';
    private const PERMISSION_REJECT = 'student.transfer.reject';
    private const PERMISSION_CANCEL = 'student.transfer.cancel';

    public function __construct(private readonly StudentClassTransferService $transfers)
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
                $search = '%'.trim($request->input('search')).'%';
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
            'activeContext' => $this->transfers->activeAcademicContextOrNull($tenantId),
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

        $transfer = $this->transfers->request(
            auth()->user(),
            (int) $data['student_id'],
            (int) $data['to_class_arm_id'],
            $data['effective_date'],
            $data['reason'],
            $request->file('supporting_document'),
            $request,
        );

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
        $transfer = $this->transfers->approve(auth()->user(), (int) $classTransfer->id, $request);

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

        $transfer = $this->transfers->reject(
            auth()->user(),
            (int) $classTransfer->id,
            $data['rejection_reason'],
            $request,
        );

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

        $transfer = $this->transfers->cancel(
            auth()->user(),
            (int) $classTransfer->id,
            $data['cancellation_reason'],
            $request,
        );

        return redirect()
            ->route('students.class-transfers.show', $transfer)
            ->with('success', 'Interclass transfer request cancelled.');
    }

    public function downloadDocument(StudentClassTransfer $classTransfer)
    {
        $this->authorizeTransfer(self::PERMISSION_VIEW);
        $transfer = $this->tenantTransfer($classTransfer);
        abort_unless($transfer->supporting_document, 404);

        if (Storage::exists($transfer->supporting_document)) {
            return Storage::download($transfer->supporting_document, null, [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
        if (Storage::disk('public')->exists($transfer->supporting_document)) {
            return Storage::disk('public')->download($transfer->supporting_document, null, [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
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
