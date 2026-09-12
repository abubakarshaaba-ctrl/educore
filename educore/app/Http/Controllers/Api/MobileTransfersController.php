<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CrossSchoolStudentTransferService;
use App\Services\StudentClassTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileTransfersController extends Controller
{
    public function __construct(
        private readonly CrossSchoolStudentTransferService $crossSchoolTransfers,
        private readonly StudentClassTransferService $classTransfers,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['all', 'cross_school', 'intra_class', 'interclass'])],
            'status' => ['nullable', Rule::in(array_values(array_unique(array_merge(StudentTransfer::STATUSES, StudentClassTransfer::STATUSES))))],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $scope = $data['scope'] ?? 'all';
        $status = trim((string) ($data['status'] ?? ''));
        $query = trim((string) ($data['q'] ?? ''));
        $limit = (int) ($data['limit'] ?? 50);

        $crossSchool = collect();
        if (in_array($scope, ['all', 'cross_school'], true)) {
            $crossSchool = $this->crossSchoolQuery($tenantId)
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($query !== '', function ($q) use ($query): void {
                    $like = '%'.$query.'%';
                    $q->where(function ($inner) use ($like): void {
                        $inner->where('student_name', 'like', $like)
                            ->orWhere('admission_number', 'like', $like);
                    });
                })
                ->latest()
                ->limit($limit)
                ->get();
        }

        $tenantIds = $crossSchool
            ->flatMap(fn (StudentTransfer $transfer) => [$transfer->from_tenant_id, $transfer->to_tenant_id])
            ->filter()
            ->unique()
            ->values();
        $tenantNames = Tenant::whereIn('id', $tenantIds)->pluck('name', 'id');

        $canViewClassTransfers = $this->canClassTransfer($user, 'student.transfer.view');
        $canRequestClassTransfers = $this->canClassTransfer($user, 'student.transfer.request');
        $canApproveClassTransfers = $this->canClassTransfer($user, 'student.transfer.approve');
        $canRejectClassTransfers = $this->canClassTransfer($user, 'student.transfer.reject');
        $canCancelClassTransfers = $this->canClassTransfer($user, 'student.transfer.cancel');

        $classTransferRows = collect();
        if ($scope !== 'cross_school' && $canViewClassTransfers) {
            $classTransferRows = StudentClassTransfer::where('tenant_id', $tenantId)
                ->with([
                    'student:id,tenant_id,admission_number,first_name,middle_name,last_name',
                    'fromClassArm.classLevel:id,name',
                    'toClassArm.classLevel:id,name',
                    'requestedBy:id,name',
                ])
                ->when($scope === 'intra_class', fn ($q) => $q->where('movement_type', StudentClassTransfer::TYPE_INTRA_CLASS))
                ->when($scope === 'interclass', fn ($q) => $q->where('movement_type', StudentClassTransfer::TYPE_INTERCLASS))
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($query !== '', function ($q) use ($query): void {
                    $like = '%'.$query.'%';
                    $q->whereHas('student', function ($studentQuery) use ($like): void {
                        $studentQuery->where('admission_number', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    });
                })
                ->latest()
                ->limit($limit)
                ->get();
        }

        $intraClass = $classTransferRows
            ->where('movement_type', StudentClassTransfer::TYPE_INTRA_CLASS)
            ->values();
        $interclass = $classTransferRows
            ->where('movement_type', StudentClassTransfer::TYPE_INTERCLASS)
            ->values();

        $destinations = Tenant::query()
            ->where('id', '!=', $tenantId)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tenant $tenant): array => ['id' => $tenant->id, 'name' => $tenant->name]);

        $students = Student::where('tenant_id', $tenantId)
            ->with('currentClassArm:id,class_level_id')
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'current_class_arm_id'])
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $this->studentName($student),
                'admission_number' => $student->admission_number,
                'class_arm_id' => $student->current_class_arm_id,
                'class_level_id' => $student->currentClassArm?->class_level_id,
            ]);

        $classArms = ClassArm::where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ClassArm $arm): array => [
                'id' => $arm->id,
                'class_level_id' => $arm->class_level_id,
                'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
            ]);

        $crossPending = $this->crossSchoolQuery($tenantId)->where('status', StudentTransfer::STATUS_PENDING);
        $intraBase = StudentClassTransfer::where('tenant_id', $tenantId)
            ->where('movement_type', StudentClassTransfer::TYPE_INTRA_CLASS);
        $interBase = StudentClassTransfer::where('tenant_id', $tenantId)
            ->where('movement_type', StudentClassTransfer::TYPE_INTERCLASS);

        return response()->json([
            'contract_version' => 2,
            'capabilities' => [
                'cross_school_request' => true,
                'cross_school_approve' => true,
                'cross_school_reject' => true,
                'intra_class_view' => $canViewClassTransfers,
                'intra_class_request' => $canRequestClassTransfers,
                'intra_class_approve' => $canApproveClassTransfers,
                'intra_class_reject' => $canRejectClassTransfers,
                'intra_class_cancel' => $canCancelClassTransfers,
                'interclass_view' => $canViewClassTransfers,
                'interclass_request' => $canRequestClassTransfers,
                'interclass_approve' => $canApproveClassTransfers,
                'interclass_reject' => $canRejectClassTransfers,
                'interclass_cancel' => $canCancelClassTransfers,
                'class_transfer_mobile_mutation' => $canRequestClassTransfers || $canApproveClassTransfers || $canRejectClassTransfers || $canCancelClassTransfers,
            ],
            'metrics' => [
                'cross_outgoing' => StudentTransfer::where('from_tenant_id', $tenantId)->count(),
                'cross_incoming' => StudentTransfer::where('to_tenant_id', $tenantId)->count(),
                'cross_pending' => (clone $crossPending)->count(),
                'intra_class_pending' => (clone $intraBase)->where('status', StudentClassTransfer::STATUS_PENDING)->count(),
                'intra_class_completed' => (clone $intraBase)->where('status', StudentClassTransfer::STATUS_COMPLETED)->count(),
                'interclass_pending' => (clone $interBase)->where('status', StudentClassTransfer::STATUS_PENDING)->count(),
                'interclass_completed' => (clone $interBase)->where('status', StudentClassTransfer::STATUS_COMPLETED)->count(),
            ],
            'cross_school' => $crossSchool->map(fn (StudentTransfer $transfer): array => [
                'id' => $transfer->id,
                'direction' => (int) $transfer->to_tenant_id === $tenantId ? 'incoming' : 'outgoing',
                'student_id' => $transfer->student_id,
                'destination_student_id' => $transfer->destination_student_id,
                'student_name' => $transfer->student_name,
                'admission_number' => $transfer->admission_number,
                'from_school' => $tenantNames->get($transfer->from_tenant_id, 'School'),
                'to_school' => $tenantNames->get($transfer->to_tenant_id, 'School'),
                'status' => $transfer->status,
                'reason' => $transfer->reason,
                'created_at' => optional($transfer->created_at)?->toIso8601String(),
                'completed_at' => optional($transfer->completed_at)?->toIso8601String(),
            ])->values(),
            'intra_class' => $intraClass->map(fn (StudentClassTransfer $transfer): array => $this->classTransferPayload($transfer))->values(),
            'interclass' => $interclass->map(fn (StudentClassTransfer $transfer): array => $this->classTransferPayload($transfer))->values(),
            'options' => [
                'destinations' => $destinations->values(),
                'students' => $students->values(),
                'class_arms' => $classArms->values(),
            ],
        ]);
    }

    public function requestCrossSchool(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q
                    ->where('tenant_id', $tenantId)
                    ->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at')),
            ],
            'to_tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'id')->where(fn ($q) => $q
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->where('id', '!=', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->crossSchoolTransfers->request(
            $user,
            (int) $data['student_id'],
            (int) $data['to_tenant_id'],
            $data['reason'] ?? null,
            $request,
        );

        return response()->json([
            'message' => 'Transfer request submitted.',
            'transfer_id' => $transfer->id,
        ], 201);
    }

    public function approveCrossSchool(Request $request, int $transfer): JsonResponse
    {
        $user = $this->guard($request);
        $completed = $this->crossSchoolTransfers->approve($user, $transfer, $request);

        return response()->json([
            'message' => 'Incoming transfer approved. A fresh receiving-school student record has been created while source-school history remains archived.',
            'destination_student_id' => $completed->destination_student_id,
        ]);
    }

    public function rejectCrossSchool(Request $request, int $transfer): JsonResponse
    {
        $user = $this->guard($request);
        $this->crossSchoolTransfers->reject($user, $transfer, $request);

        return response()->json(['message' => 'Incoming transfer rejected.']);
    }

    public function requestIntraClass(Request $request): JsonResponse
    {
        return $this->requestClassTransfer($request, StudentClassTransfer::TYPE_INTRA_CLASS, 'Intra-class');
    }

    public function approveIntraClass(Request $request, int $transfer): JsonResponse
    {
        return $this->approveClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTRA_CLASS, 'Intra-class');
    }

    public function rejectIntraClass(Request $request, int $transfer): JsonResponse
    {
        return $this->rejectClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTRA_CLASS, 'Intra-class');
    }

    public function cancelIntraClass(Request $request, int $transfer): JsonResponse
    {
        return $this->cancelClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTRA_CLASS, 'Intra-class');
    }

    public function requestInterclass(Request $request): JsonResponse
    {
        return $this->requestClassTransfer($request, StudentClassTransfer::TYPE_INTERCLASS, 'Interclass');
    }

    public function approveInterclass(Request $request, int $transfer): JsonResponse
    {
        return $this->approveClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTERCLASS, 'Interclass');
    }

    public function rejectInterclass(Request $request, int $transfer): JsonResponse
    {
        return $this->rejectClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTERCLASS, 'Interclass');
    }

    public function cancelInterclass(Request $request, int $transfer): JsonResponse
    {
        return $this->cancelClassTransfer($request, $transfer, StudentClassTransfer::TYPE_INTERCLASS, 'Interclass');
    }

    private function requestClassTransfer(Request $request, string $movementType, string $label): JsonResponse
    {
        $user = $this->guard($request);
        $this->authorizeClassTransfer($user, 'student.transfer.request');
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q
                    ->where('tenant_id', $tenantId)
                    ->where('status', Student::STATUS_ACTIVE)
                    ->whereNotNull('current_class_arm_id')
                    ->whereNull('deleted_at')),
            ],
            'to_class_arm_id' => [
                'required',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $transfer = $this->classTransfers->request(
            $user,
            (int) $data['student_id'],
            (int) $data['to_class_arm_id'],
            $data['effective_date'],
            $data['reason'],
            null,
            $request,
            $movementType,
        );

        return response()->json([
            'message' => $label.' transfer request created.',
            'transfer_id' => $transfer->id,
        ], 201);
    }

    private function approveClassTransfer(Request $request, int $transfer, string $movementType, string $label): JsonResponse
    {
        $user = $this->guard($request);
        $this->authorizeClassTransfer($user, 'student.transfer.approve');
        $this->assertTransferType((int) $user->tenant_id, $transfer, $movementType);
        $this->classTransfers->approve($user, $transfer, $request);

        return response()->json(['message' => $label.' transfer approved and completed.']);
    }

    private function rejectClassTransfer(Request $request, int $transfer, string $movementType, string $label): JsonResponse
    {
        $user = $this->guard($request);
        $this->authorizeClassTransfer($user, 'student.transfer.reject');
        $this->assertTransferType((int) $user->tenant_id, $transfer, $movementType);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->classTransfers->reject($user, $transfer, $data['reason'], $request);

        return response()->json(['message' => $label.' transfer request rejected.']);
    }

    private function cancelClassTransfer(Request $request, int $transfer, string $movementType, string $label): JsonResponse
    {
        $user = $this->guard($request);
        $this->authorizeClassTransfer($user, 'student.transfer.cancel');
        $this->assertTransferType((int) $user->tenant_id, $transfer, $movementType);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->classTransfers->cancel($user, $transfer, $data['reason'], $request);

        return response()->json(['message' => $label.' transfer request cancelled.']);
    }

    private function classTransferPayload(StudentClassTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'movement_type' => $transfer->movement_type,
            'student_id' => $transfer->student_id,
            'student_name' => $transfer->student ? $this->studentName($transfer->student) : 'Student',
            'admission_number' => $transfer->student?->admission_number,
            'from_class' => $transfer->fromClassArm?->full_name,
            'to_class' => $transfer->toClassArm?->full_name,
            'from_class_level_id' => $transfer->fromClassArm?->class_level_id,
            'to_class_level_id' => $transfer->toClassArm?->class_level_id,
            'effective_date' => optional($transfer->effective_date)?->toDateString(),
            'status' => $transfer->status,
            'reason' => $transfer->reason,
            'requested_by' => $transfer->requestedBy?->name,
            'requested_by_id' => $transfer->requested_by,
            'created_at' => optional($transfer->created_at)?->toIso8601String(),
        ];
    }

    private function assertTransferType(int $tenantId, int $transferId, string $movementType): void
    {
        StudentClassTransfer::where('tenant_id', $tenantId)
            ->whereKey($transferId)
            ->where('movement_type', $movementType)
            ->firstOrFail();
    }

    private function crossSchoolQuery(int $tenantId)
    {
        return StudentTransfer::query()->where(function ($query) use ($tenantId): void {
            $query->where('from_tenant_id', $tenantId)
                ->orWhere('to_tenant_id', $tenantId);
        });
    }

    private function canClassTransfer(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    private function authorizeClassTransfer(User $user, string $permission): void
    {
        abort_unless($this->canClassTransfer($user, $permission), 403, 'Class transfer permission required.');
    }

    private function studentName(Student $student): string
    {
        return trim(implode(' ', array_filter([
            $student->first_name,
            $student->middle_name,
            $student->last_name,
        ])));
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School transfer access required.');
        abort_unless($user->tenant_id, 403, 'School transfer access required.');
        abort_unless($user->canAccessModule('transfers'), 403, 'Student transfer access required.');

        return $user;
    }
}
