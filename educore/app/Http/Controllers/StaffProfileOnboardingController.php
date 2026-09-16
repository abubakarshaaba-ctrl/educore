<?php

namespace App\Http\Controllers;

use App\Models\StaffProfileSubmission;
use App\Models\StaffWorkHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffProfileOnboardingController extends Controller
{
    public function show(string $token)
    {
        $tenant = Tenant::where('staff_onboarding_token', $token)->firstOrFail();
        abort_unless($tenant->isPublicPortalAvailable(), 404);

        return view('staff.onboarding-public', [
            'tenant' => $tenant,
            'token' => $token,
            'highestQualifications' => config('staff.highest_qualifications', []),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $tenant = Tenant::where('staff_onboarding_token', $token)->firstOrFail();
        abort_unless($tenant->isPublicPortalAvailable(), 404);

        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'email' => ['required','email','max:180', Rule::unique('users','email'), Rule::unique('staff_profile_submissions','email')->where('tenant_id',$tenant->id)],
            'phone' => ['nullable','string','max:30'],
            'date_of_birth' => ['nullable','date','before:today'],
            'gender' => ['required','in:male,female'],
            'qualification' => ['required', Rule::in(config('staff.highest_qualifications', []))],
            'address' => ['nullable','string','max:255'],
            'employment_started_at' => ['nullable','date','before_or_equal:today'],
            'position_title' => ['required','string','max:255'],
            'department_name' => ['nullable','string','max:255'],
            'employment_type' => ['nullable','string','max:100'],
            'functional_role' => ['nullable','string','max:150'],
            'grade_level' => ['nullable','string','max:100'],
            'appointment_type' => ['nullable','string','max:100'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        StaffProfileSubmission::create([
            ...collect($data)->except(['password','password_confirmation'])->all(),
            'tenant_id' => $tenant->id,
            'password_hash' => Hash::make($data['password']),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Your staff profile has been submitted successfully. The school administrator will review and activate your account.');
    }

    public function manage(Request $request)
    {
        $tenant = auth()->user()->tenant;
        abort_unless(auth()->user()->canManage('staff'), 403);

        if (!$tenant->staff_onboarding_token) {
            $tenant->forceFill(['staff_onboarding_token' => $this->newToken()])->save();
        }

        $submissions = StaffProfileSubmission::where('tenant_id', $tenant->id)
            ->orderByRaw("CASE WHEN status='pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20);

        return view('staff.onboarding-manage', [
            'tenant' => $tenant,
            'submissions' => $submissions,
            'joinUrl' => route('staff.join.show', $tenant->staff_onboarding_token),
            'roleLabels' => collect(User::ROLE_LABELS)->only(User::staffRoleNames()),
        ]);
    }

    public function rotate()
    {
        $tenant = auth()->user()->tenant;
        abort_unless(auth()->user()->canManage('staff'), 403);

        $tenant->forceFill(['staff_onboarding_token' => $this->newToken()])->save();

        return back()->with('success', 'A new staff profile link has been generated. The previous link is no longer valid.');
    }

    public function approve(Request $request, StaffProfileSubmission $submission)
    {
        $tenant = auth()->user()->tenant;
        abort_unless(auth()->user()->canManage('staff') && (int)$submission->tenant_id === (int)$tenant->id, 404);
        abort_if($submission->status !== 'pending', 422, 'This submission has already been reviewed.');

        if ($error = PlanLimitService::checkStaffLimit($tenant)) {
            return back()->withErrors(['limit' => $error]);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(User::staffRoleNames())],
        ]);

        DB::transaction(function () use ($submission, $data, $tenant) {
            $staffId = $this->generateStaffId($tenant->id);
            $role = User::canonicalRole($data['role']);

            $staff = User::create([
                'tenant_id' => $tenant->id,
                'name' => $submission->name,
                'email' => $submission->email,
                'role' => $role,
                'password' => $submission->password_hash,
                'phone' => $submission->phone,
                'gender' => $submission->gender,
                'qualification' => $submission->qualification,
                'qualifications' => [$submission->qualification],
                'date_of_birth' => $submission->date_of_birth,
                'address' => $submission->address,
                'staff_id' => $staffId,
                'is_active' => true,
                'employment_status' => User::STAFF_STATUS_ACTIVE,
                'employment_started_at' => $submission->employment_started_at ?: now()->toDateString(),
                'status_changed_at' => now(),
            ]);
            $staff->assignRole($role);

            StaffWorkHistory::create([
                'tenant_id' => $tenant->id,
                'user_id' => $staff->id,
                'position_title' => $submission->position_title,
                'department_name' => $submission->department_name,
                'employment_type' => $submission->employment_type,
                'functional_role' => $submission->functional_role,
                'grade_level' => $submission->grade_level,
                'appointment_type' => $submission->appointment_type,
                'start_date' => $submission->employment_started_at ?: now()->toDateString(),
                'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
                'reason' => 'Created from staff self-profile onboarding.',
                'recorded_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $submission->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
        });

        return back()->with('success', 'Staff profile approved and account activated.');
    }

    public function reject(StaffProfileSubmission $submission)
    {
        $tenant = auth()->user()->tenant;
        abort_unless(auth()->user()->canManage('staff') && (int)$submission->tenant_id === (int)$tenant->id, 404);
        $submission->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
        return back()->with('success', 'Staff profile submission rejected.');
    }

    private function newToken(): string
    {
        do { $token = Str::lower(Str::random(8)); }
        while (Tenant::where('staff_onboarding_token', $token)->exists());
        return $token;
    }

    private function generateStaffId(int $tenantId): string
    {
        $last = User::where('tenant_id', $tenantId)->whereNotNull('staff_id')->orderByDesc('id')->value('staff_id');
        $num = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1001;
        return 'STF' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
