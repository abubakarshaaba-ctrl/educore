<?php

namespace App\Http\Controllers;

use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    private function ensureTenantStaff(User $staff): void
    {
        abort_if(
            (int) $staff->tenant_id !== (int) auth()->user()->tenant_id
                || $staff->is_super_admin
                || !$staff->isTenantStaff()
                || !$staff->isEmploymentActive(),
            404
        );
    }

    public function index(Request $request)
    {
        $tid = auth()->user()->tenant_id;

        $query = User::activeStaff($tid);

        if ($request->filled('role'))   { $query->whereIn('role', User::roleAliasesFor($request->role)); }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('name',     'like', "%$s%")
                  ->orWhere('email',    'like', "%$s%")
                  ->orWhere('staff_id', 'like', "%$s%")
            );
        }

        $staff = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if ($error = PlanLimitService::checkStaffLimit($tenant)) {
            return back()->withErrors(['limit' => $error]);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'role'     => ['required', 'in:' . implode(',', User::staffRoleNames())],
            'password' => ['required', Password::min(8)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'gender'   => ['nullable', 'in:male,female'],
            'staff_id' => ['nullable', 'string', 'max:40', Rule::unique('users', 'staff_id')->where('tenant_id', auth()->user()->tenant_id)],
            'employment_started_at' => ['required', 'date', 'before_or_equal:today'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'functional_role' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
        ]);

        // Auto-generate staff_id if not provided
        $staffId = $validated['staff_id'] ?? $this->generateStaffId();
        $role = User::canonicalRole($validated['role']);

        $staff = DB::transaction(function () use ($validated, $staffId, $role) {
            $staff = User::create([
                'tenant_id'  => auth()->user()->tenant_id,
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'role'       => $role,
                'password'   => Hash::make($validated['password']),
                'phone'      => $validated['phone'] ?? null,
                'gender'     => $validated['gender'] ?? null,
                'staff_id'   => $staffId,
                'is_active'  => true,
                'employment_status' => User::STAFF_STATUS_ACTIVE,
                'employment_started_at' => $validated['employment_started_at'],
                'employment_ended_at' => null,
                'status_changed_at' => now(),
            ]);
            $staff->assignRole($role);

            StaffWorkHistory::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => $staff->id,
                'position_title' => $validated['position_title'],
                'department_name' => $validated['department_name'] ?? null,
                'employment_type' => $validated['employment_type'] ?? null,
                'functional_role' => $validated['functional_role'] ?? null,
                'grade_level' => $validated['grade_level'] ?? null,
                'appointment_type' => $validated['appointment_type'] ?? null,
                'start_date' => $validated['employment_started_at'],
                'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
                'reason' => 'Initial staff account creation.',
                'recorded_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $staff;
        });

        return redirect()->route('staff.index')
            ->with('success', "Staff account created for {$validated['name']}. Staff ID: {$staffId}");
    }

    public function show(User $staff)
    {
        $this->ensureTenantStaff($staff);
        $staff->load(['classArms.classLevel', 'currentWorkHistory', 'staffStatusHistories.changedBy', 'workHistories']);
        return view('staff.show', compact('staff'));
    }

    public function edit(User $staff)
    {
        $this->ensureTenantStaff($staff);
        return view('staff.edit', compact('staff'));
    }

    public function update(Request $request, User $staff)
    {
        $this->ensureTenantStaff($staff);
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:users,email,' . $staff->id],
            'role'           => ['required', 'in:' . implode(',', User::staffRoleNames())],
            'phone'          => ['nullable', 'string', 'max:20'],
            'staff_id'       => ['nullable', 'string', 'max:40', Rule::unique('users', 'staff_id')->where('tenant_id', auth()->user()->tenant_id)->ignore($staff->id)],
            'gender'         => ['nullable', 'in:male,female'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'max:20'],
        ]);
        $role = User::canonicalRole($validated['role']);

        $staff->update([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'role'           => $role,
            'phone'          => $validated['phone'] ?? $staff->phone,
            'staff_id'       => $validated['staff_id'] ?? $staff->staff_id,
            'gender'         => $validated['gender'] ?? null,
            'qualifications' => $validated['qualifications'] ?? [],
        ]);
        $staff->syncRoles($role);

        return redirect()->route('staff.show', $staff)
            ->with('success', 'Staff record updated.');
    }

    public function resetPassword(Request $request, User $staff)
    {
        $this->ensureTenantStaff($staff);
        $validated = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);
        $staff->update(['password' => Hash::make($validated['password'])]);
        return back()->with('success', 'Password reset successfully.');
    }

    public function toggle(User $staff)
    {
        $this->ensureTenantStaff($staff);
        return redirect()
            ->route('staff.status.show', $staff)
            ->withErrors(['staff' => 'Use the staff lifecycle workflow to deactivate or reinstate staff.']);
    }

    private function generateStaffId(): string
    {
        $tid    = auth()->user()->tenant_id;
        $prefix = 'STF';
        $last   = User::where('tenant_id', $tid)
            ->whereNotNull('staff_id')
            ->orderByDesc('id')->value('staff_id');

        $num = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1001;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
