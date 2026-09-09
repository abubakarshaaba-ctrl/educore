<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\PortalAccountService;
use Illuminate\Http\Request;

/**
 * Admin page to create and manage student/parent portal identities.
 */
class PortalAccountController extends Controller
{
    public function __construct(private PortalAccountService $accounts) {}

    private function tid(): int
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_unless($tenantId, 403, 'School account required.');
        return (int) $tenantId;
    }

    public function index()
    {
        $tid = $this->tid();
        $students = Student::where('tenant_id', $tid)
            ->where('status', Student::STATUS_ACTIVE)
            ->with(['currentClassArm.classLevel'])
            ->orderBy('first_name')
            ->get();

        $studentUserIds = $students->pluck('user_id')->filter()->unique();
        $studentUsers = User::where('tenant_id', $tid)
            ->whereIn('id', $studentUserIds)
            ->where('role', 'student')
            ->get()
            ->keyBy('id');
        $studentAccountMap = $students->mapWithKeys(
            fn (Student $student) => [$student->id => $student->user_id ? $studentUsers->get($student->user_id) : null]
        );

        $guardians = Guardian::where('tenant_id', $tid)
            ->with(['students.currentClassArm.classLevel'])
            ->orderBy('first_name')
            ->get();
        $guardianUserIds = $guardians->pluck('user_id')->filter()->unique();
        $guardianUsers = User::where('tenant_id', $tid)
            ->whereIn('id', $guardianUserIds)
            ->where('role', 'parent')
            ->get()
            ->keyBy('id');
        $guardianAccountMap = $guardians->mapWithKeys(
            fn (Guardian $guardian) => [$guardian->id => $guardian->user_id ? $guardianUsers->get($guardian->user_id) : null]
        );

        return view('portal.accounts', compact(
            'students', 'studentAccountMap',
            'guardians', 'guardianAccountMap'
        ));
    }

    public function createStudentAccount(Request $request, Student $student)
    {
        $tid = $this->tid();
        abort_unless((int) $student->tenant_id === $tid, 404);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->accounts->createStudentAccount(
            tenantId: $tid,
            student: $student,
            email: $data['email'],
            password: $data['password'],
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', "Portal account created for {$student->full_name}. The temporary password was not stored or displayed; share the password you entered through an approved private channel.");
    }

    public function createGuardianAccount(Request $request, Guardian $guardian)
    {
        $tid = $this->tid();
        abort_unless((int) $guardian->tenant_id === $tid, 404);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->accounts->createGuardianAccount(
            tenantId: $tid,
            guardian: $guardian,
            email: $data['email'],
            password: $data['password'],
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', "Portal account created for {$guardian->full_name}. The temporary password was not stored or displayed; share the password you entered through an approved private channel.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $tid = $this->tid();
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $updated = $this->accounts->resetPassword(
            tenantId: $tid,
            portalUser: $user,
            password: $data['password'],
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', "Password reset for {$updated->name}. Existing app/browser sessions and push subscriptions were revoked.");
    }

    public function toggleAccess(Request $request, User $user)
    {
        $updated = $this->accounts->toggleAccess(
            tenantId: $this->tid(),
            portalUser: $user,
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', "{$updated->name} portal access ".($updated->is_active ? 'enabled' : 'disabled').'.');
    }

    public function bulkCreateStudents(Request $request)
    {
        $result = $this->accounts->bulkCreateStudents(
            tenantId: $this->tid(),
            actor: $request->user(),
            request: $request,
        );

        return back()->with(
            'success',
            "{$result['created']} student portal accounts created with secure random credentials; users must use Forgot Password to set their own password. {$result['skipped']} skipped."
        );
    }
}
