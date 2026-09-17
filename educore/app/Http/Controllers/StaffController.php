<?php

namespace App\Http\Controllers;

use App\Models\StaffProfileSubmission;
use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\StaffIdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function __construct(private readonly StaffIdGenerator $staffIdGenerator)
    {
    }

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
        return view('staff.create', [
            'highestQualifications' => config('staff.highest_qualifications', []),
            'departments' => config('staff.departments', []),
            'employmentTypes' => config('staff.employment_types', []),
            'appointmentTypes' => config('staff.appointment_types', []),
        ]);
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
            'gender'   => ['required', 'in:male,female'],
            'qualification' => ['required', Rule::in(config('staff.highest_qualifications', []))],
            'staff_id' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('users', 'staff_id'),
                Rule::unique('staff_profile_submissions', 'staff_id'),
            ],
            'employment_started_at' => ['required', 'date', 'before_or_equal:today'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', Rule::in(config('staff.departments', []))],
            'employment_type' => ['required', Rule::in(config('staff.employment_types', []))],
            'appointment_type' => ['required', Rule::in(config('staff.appointment_types', []))],
        ]);

        $staffId = $validated['staff_id'] ?? $this->staffIdGenerator->generate();
        $role = User::canonicalRole($validated['role']);

        $staff = DB::transaction(function () use ($validated, $staffId, $role) {
            $staff = User::create([
                'tenant_id'  => auth()->user()->tenant_id,
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'role'       => $role,
                'password'   => Hash::make($validated['password']),
                'phone'      => $validated['phone'] ?? null,
                'gender'     => $validated['gender'],
                'qualification' => $validated['qualification'],
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
                'employment_type' => $validated['employment_type'],
                'functional_role' => null,
                'grade_level' => null,
                'appointment_type' => $validated['appointment_type'],
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
        return view('staff.show', [
            'staff' => $staff,
            'highestQualifications' => config('staff.highest_qualifications', []),
        ]);
    }

    public function edit(User $staff)
    {
        $this->ensureTenantStaff($staff);
        return view('staff.edit', [
            'staff' => $staff,
            'highestQualifications' => config('staff.highest_qualifications', []),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $this->ensureTenantStaff($staff);

        // Staff IDs used to be generated per tenant. That means legacy records can
        // legitimately share an ID with a record in another tenant. Do not block an
        // unrelated profile edit merely because the existing legacy ID is duplicated.
        // Global uniqueness is still enforced whenever the ID itself is changed.
        $requestedStaffId = $request->input('staff_id');
        $staffIdIsChanging = filled($requestedStaffId)
            && (string) $requestedStaffId !== (string) $staff->staff_id;

        $staffIdRules = ['nullable', 'string', 'max:40'];
        if ($staffIdIsChanging) {
            $staffIdRules[] = Rule::unique('users', 'staff_id')->ignore($staff->id);
            $staffIdRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                if (
                    $value
                    && StaffProfileSubmission::withoutTenantScope()
                        ->where('staff_id', $value)
                        ->where('status', 'pending')
                        ->exists()
                ) {
                    $fail('This Staff ID is already reserved for a pending staff onboarding submission.');
                }
            };
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:users,email,' . $staff->id],
            'role'           => ['required', 'in:' . implode(',', User::staffRoleNames())],
            'phone'          => ['nullable', 'string', 'max:20'],
            'staff_id'       => $staffIdRules,
            'gender'         => ['nullable', 'in:male,female'],
            'qualification'  => ['nullable', Rule::in(config('staff.highest_qualifications', []))],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'max:20'],
        ]);

        $role = User::canonicalRole($validated['role']);

        DB::transaction(function () use ($staff, $validated, $role): void {
            $staff->update([
                'name'           => $validated['name'],
                'email'          => $validated['email'],
                'role'           => $role,
                'phone'          => array_key_exists('phone', $validated) ? $validated['phone'] : $staff->phone,
                'staff_id'       => filled($validated['staff_id'] ?? null) ? $validated['staff_id'] : $staff->staff_id,
                'gender'         => $validated['gender'] ?? null,
                'qualification'  => $validated['qualification'] ?? null,
                'qualifications' => $validated['qualifications'] ?? $staff->qualifications ?? [],
            ]);
            $staff->syncRoles($role);
        });

        return redirect()->route('staff.show', $staff)
            ->with('success', 'Staff record updated successfully.');
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
}
