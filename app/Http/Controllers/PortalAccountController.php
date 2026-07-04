<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PortalAccountController
 *
 * Admin page to create / manage student and parent login accounts.
 * Accessed at /portal-accounts
 */
class PortalAccountController extends Controller
{
    private function tid(): int { return auth()->user()->tenant_id; }

    private function ensureTenantPortalUser(User $user): void
    {
        abort_if(
            (int) $user->tenant_id !== $this->tid()
                || !in_array($user->roleKey(), User::ROLES_PORTAL, true),
            404
        );
    }

    public function index()
    {
        // Students with and without portal accounts
        $students = Student::where('tenant_id', $this->tid())
            ->where('status', Student::STATUS_ACTIVE)
            ->with(['currentClassArm.classLevel'])
            ->orderBy('first_name')->get();

        $studentUsersById = User::where('tenant_id', $this->tid())
            ->where('role', 'student')
            ->get()->keyBy('id');

        // Map student → user
        $studentAccountMap = $students->mapWithKeys(function ($s) {
            return [$s->id => $s->user_id ? User::where('tenant_id', $this->tid())->find($s->user_id) : null];
        });

        // Guardians with and without portal accounts
        $guardians = Guardian::where('tenant_id', $this->tid())
            ->with(['students.currentClassArm.classLevel'])
            ->orderBy('first_name')->get();

        $guardianAccountMap = $guardians->mapWithKeys(function ($g) {
            return [$g->id => $g->user_id ? User::where('tenant_id', $this->tid())->find($g->user_id) : null];
        });

        return view('portal.accounts', compact(
            'students', 'studentAccountMap',
            'guardians', 'guardianAccountMap'
        ));
    }

    // ── Create student portal account ─────────────────────────────────
    public function createStudentAccount(Request $request, Student $student)
    {
        if ($student->user_id && User::where('tenant_id', $this->tid())->find($student->user_id)) {
            return back()->withErrors(['error' => 'Portal account already exists for this student.']);
        }

        $data = $request->validate([
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $password = !empty($data['password'] ?? null) ? $data['password'] : $student->admission_number;

        $user = User::create([
            'tenant_id' => $this->tid(),
            'name'      => $student->full_name,
            'email'     => $data['email'],
            'password'  => Hash::make($password),
            'role'      => 'student',
            'is_active' => true,
        ]);

        $student->update(['user_id' => $user->id]);

        return back()->with('success', "Portal account created for {$student->full_name}. Login: {$data['email']} / Password: {$password}");
    }

    // ── Create parent portal account ──────────────────────────────────
    public function createGuardianAccount(Request $request, Guardian $guardian)
    {
        if ($guardian->user_id && User::where('tenant_id', $this->tid())->find($guardian->user_id)) {
            return back()->withErrors(['error' => 'Portal account already exists for this guardian.']);
        }

        $data = $request->validate([
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $password = !empty($data['password'] ?? null) ? $data['password'] : 'portal' . rand(1000, 9999);

        $user = User::create([
            'tenant_id' => $this->tid(),
            'name'      => $guardian->full_name,
            'email'     => $data['email'],
            'password'  => Hash::make($password),
            'role'      => 'parent',
            'is_active' => true,
        ]);

        $guardian->update(['user_id' => $user->id]);

        return back()->with('success', "Portal account created for {$guardian->full_name}. Login: {$data['email']} / Password: {$password}");
    }

    // ── Reset portal password ─────────────────────────────────────────
    public function resetPassword(Request $request, User $user)
    {
        $this->ensureTenantPortalUser($user);
        $data     = $request->validate(['password' => ['required', 'string', 'min:6']]);
        $user->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', "Password reset for {$user->name}.");
    }

    // ── Toggle portal access ──────────────────────────────────────────
    public function toggleAccess(User $user)
    {
        $this->ensureTenantPortalUser($user);
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', "{$user->name} portal access " . ($user->is_active ? 'enabled' : 'disabled') . '.');
    }

    // ── Bulk create student accounts ───────────────────────────────────
    public function bulkCreateStudents(Request $request)
    {
        $tid = $this->tid();
        $created = 0;
        $skipped = 0;

        $students = Student::where('tenant_id', $tid)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereNull('user_id')
            ->get();

        foreach ($students as $student) {
            if (!$student->email) { $skipped++; continue; }
            if (User::where('email', $student->email)->exists()) { $skipped++; continue; }

            $user = User::create([
                'tenant_id' => $tid,
                'name'      => $student->full_name,
                'email'     => $student->email,
                'password'  => Hash::make($student->admission_number),
                'role'      => 'student',
                'is_active' => true,
            ]);
            $student->update(['user_id' => $user->id]);
            $created++;
        }

        return back()->with('success', "{$created} student portal accounts created. {$skipped} skipped (no email or already exists).");
    }
}
