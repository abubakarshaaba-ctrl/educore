<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\PortalAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilePortalAccountsController extends Controller
{
    public function __construct(private PortalAccountService $accounts) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;

        $students = Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->with(['currentClassArm.classLevel'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $studentAccounts = User::where('tenant_id', $tenantId)
            ->where('role', 'student')
            ->whereIn('id', $students->pluck('user_id')->filter())
            ->get(['id', 'email', 'is_active'])
            ->keyBy('id');

        $guardians = Guardian::where('tenant_id', $tenantId)
            ->with(['students:id,tenant_id,first_name,last_name'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $guardianAccounts = User::where('tenant_id', $tenantId)
            ->where('role', 'parent')
            ->whereIn('id', $guardians->pluck('user_id')->filter())
            ->get(['id', 'email', 'is_active'])
            ->keyBy('id');

        $studentRows = $students->map(function (Student $student) use ($studentAccounts): array {
            $account = $student->user_id ? $studentAccounts->get($student->user_id) : null;
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'email' => $student->email,
                'class' => $student->currentClassArm?->name,
                'class_level' => $student->currentClassArm?->classLevel?->name,
                'account' => $account ? [
                    'user_id' => $account->id,
                    'email' => $account->email,
                    'active' => (bool) $account->is_active,
                ] : null,
            ];
        })->values();

        $guardianRows = $guardians->map(function (Guardian $guardian) use ($guardianAccounts): array {
            $account = $guardian->user_id ? $guardianAccounts->get($guardian->user_id) : null;
            return [
                'id' => $guardian->id,
                'name' => $guardian->full_name,
                'email' => $guardian->email,
                'phone' => $guardian->phone,
                'children' => $guardian->students->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                ])->values(),
                'account' => $account ? [
                    'user_id' => $account->id,
                    'email' => $account->email,
                    'active' => (bool) $account->is_active,
                ] : null,
            ];
        })->values();

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('portal-accounts')],
            'summary' => [
                'students' => $students->count(),
                'student_accounts' => $studentRows->whereNotNull('account')->count(),
                'guardians' => $guardians->count(),
                'guardian_accounts' => $guardianRows->whereNotNull('account')->count(),
                'inactive_accounts' => $studentRows->merge($guardianRows)
                    ->filter(fn (array $row): bool => isset($row['account']) && !$row['account']['active'])
                    ->count(),
            ],
            'students' => $studentRows,
            'guardians' => $guardianRows,
        ]);
    }

    public function createStudent(Request $request, int $student): JsonResponse
    {
        $user = $this->guard($request, true);
        $tenantId = (int) $user->tenant_id;
        $record = Student::where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE)->findOrFail($student);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);
        $account = $this->accounts->createStudentAccount($tenantId, $record, $data['email'], $data['password'], $user, $request);

        return response()->json([
            'message' => 'Student portal account created. Share the temporary password through an approved private channel.',
            'account' => $this->accountPayload($account),
        ], 201);
    }

    public function createGuardian(Request $request, int $guardian): JsonResponse
    {
        $user = $this->guard($request, true);
        $tenantId = (int) $user->tenant_id;
        $record = Guardian::where('tenant_id', $tenantId)->findOrFail($guardian);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);
        $account = $this->accounts->createGuardianAccount($tenantId, $record, $data['email'], $data['password'], $user, $request);

        return response()->json([
            'message' => 'Parent portal account created. Share the temporary password through an approved private channel.',
            'account' => $this->accountPayload($account),
        ], 201);
    }

    public function resetPassword(Request $request, int $portalUser): JsonResponse
    {
        $user = $this->guard($request, true);
        $tenantId = (int) $user->tenant_id;
        $target = User::where('tenant_id', $tenantId)
            ->whereIn('role', User::ROLES_PORTAL)
            ->findOrFail($portalUser);
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:255']]);
        $updated = $this->accounts->resetPassword($tenantId, $target, $data['password'], $user, $request);

        return response()->json([
            'message' => 'Password reset. Existing sessions and push subscriptions were revoked.',
            'account' => $this->accountPayload($updated),
        ]);
    }

    public function toggle(Request $request, int $portalUser): JsonResponse
    {
        $user = $this->guard($request, true);
        $tenantId = (int) $user->tenant_id;
        $target = User::where('tenant_id', $tenantId)
            ->whereIn('role', User::ROLES_PORTAL)
            ->findOrFail($portalUser);
        $updated = $this->accounts->toggleAccess($tenantId, $target, $user, $request);

        return response()->json([
            'message' => 'Portal access '.($updated->is_active ? 'enabled.' : 'disabled; existing sessions were revoked.'),
            'account' => $this->accountPayload($updated),
        ]);
    }

    public function bulkStudents(Request $request): JsonResponse
    {
        $user = $this->guard($request, true);
        $result = $this->accounts->bulkCreateStudents((int) $user->tenant_id, $user, $request);

        return response()->json([
            'message' => "{$result['created']} student accounts created with secure random credentials. Users must set their password through Forgot Password. {$result['skipped']} skipped.",
            ...$result,
        ]);
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School staff access required.');
        abort_unless($user->tenant_id, 403, 'School account required.');
        abort_unless(
            $manage ? $user->canManage('portal-accounts') : $user->canAccessModule('portal-accounts'),
            403,
            $manage ? 'Portal-account management permission required.' : 'Portal-account access required.'
        );

        return $user;
    }

    private function accountPayload(User $user): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'active' => (bool) $user->is_active,
            'role' => $user->roleKey(),
        ];
    }
}
