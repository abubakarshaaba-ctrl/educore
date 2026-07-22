<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private const ROLES = [
        'admin', 'principal', 'head', 'head_teacher',
        'vice_principal', 'academic_administrator',
    ];

    public function dashboard(Request $request)
    {
        $user = $this->administrator($request);
        $tenantId = $user->tenant_id;
        $session = AcademicSession::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $term = Term::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $attendance = AttendanceRecord::where('tenant_id', $tenantId)
            ->whereDate('attendance_date', today());
        $attendanceTotal = (clone $attendance)->count();
        $present = (clone $attendance)->whereIn('status', ['present', 'late'])->count();

        $data = [
            'administrator' => [
                'name' => $user->name,
                'role' => $user->roleLabel(),
                'role_key' => $user->roleKey(),
            ],
            'academic_period' => [
                'session' => $session?->name ?? 'Not configured',
                'term' => $term?->name ?? 'Not configured',
            ],
            'metrics' => [
                'students' => Student::where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE)->count(),
                'staff' => User::where('tenant_id', $tenantId)->where('is_active', true)
                    ->whereNotIn('role', ['student', 'parent'])->count(),
                'attendance_rate' => $attendanceTotal > 0 ? round(($present / $attendanceTotal) * 100, 1) : null,
                'pending_admissions' => Admission::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            ],
            'operations' => [
                'classes' => ClassArm::where('tenant_id', $tenantId)->count(),
                'subjects' => Subject::where('tenant_id', $tenantId)->count(),
                'attendance_marked' => $attendanceTotal,
            ],
        ];

        if ($this->allows($user, 'fees')) {
            $invoices = Invoice::where('tenant_id', $tenantId);
            $data['finance'] = [
                'billed' => (float) (clone $invoices)->sum('total_amount'),
                'collected' => (float) (clone $invoices)->sum('amount_paid'),
                'outstanding' => (float) (clone $invoices)
                    ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as balance')->value('balance'),
            ];
        }

        return response()->json($data);
    }

    public function students(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allows($user, 'students'), 403, 'You do not have access to student records.');

        $students = Student::where('tenant_id', $user->tenant_id)
            ->with('currentClassArm.classLevel:id,name')
            ->where('status', Student::STATUS_ACTIVE)
            ->latest()->limit(50)->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
                'gender' => $student->gender,
                'status' => $student->status,
            ]);

        return response()->json(['students' => $students]);
    }

    public function staff(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allows($user, 'staff'), 403, 'You do not have access to staff records.');

        $staff = User::where('tenant_id', $user->tenant_id)
            ->whereNotIn('role', ['student', 'parent'])
            ->orderBy('name')->limit(50)->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'role' => $member->roleLabel(),
                'active' => (bool) $member->is_active,
            ]);

        return response()->json(['staff' => $staff]);
    }

    public function academics(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allowsAny($user, ['classes', 'subjects', 'timetable']), 403,
            'You do not have access to academic administration.');

        $arms = ClassArm::where('tenant_id', $user->tenant_id)
            ->with(['classLevel:id,name', 'formTutor:id,name'])
            ->withCount(['students' => fn ($query) => $query->where('status', Student::STATUS_ACTIVE)])
            ->get()->sortBy('full_name')->values()
            ->map(fn (ClassArm $arm) => [
                'id' => $arm->id,
                'name' => $arm->full_name,
                'students' => $arm->students_count,
                'form_tutor' => $arm->formTutor?->name ?? 'Not assigned',
            ]);

        return response()->json([
            'classes' => $arms,
            'subject_count' => Subject::where('tenant_id', $user->tenant_id)->count(),
        ]);
    }

    public function finance(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allows($user, 'fees'), 403, 'You do not have access to finance.');

        $query = Invoice::where('tenant_id', $user->tenant_id);
        $recent = (clone $query)->with('student:id,first_name,last_name')
            ->latest()->limit(30)->get()->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'student' => trim(($invoice->student?->first_name ?? '').' '.($invoice->student?->last_name ?? '')),
                'total' => (float) $invoice->total_amount,
                'paid' => (float) $invoice->amount_paid,
                'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
                'status' => $invoice->status,
            ]);

        return response()->json([
            'summary' => [
                'billed' => (float) (clone $query)->sum('total_amount'),
                'collected' => (float) (clone $query)->sum('amount_paid'),
                'outstanding' => (float) (clone $query)
                    ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as balance')->value('balance'),
            ],
            'invoices' => $recent,
        ]);
    }

    public function updateStudent(Request $request, Student $student)
    {
        $user = $this->administrator($request);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id && $this->allows($user, 'students'), 404);
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(Student::LIFECYCLE_STATUSES)],
            'first_name' => ['sometimes', 'string', 'max:100'], 'last_name' => ['sometimes', 'string', 'max:100'],
            'gender' => ['sometimes', 'in:male,female,other'],
            'current_class_arm_id' => ['sometimes', Rule::exists('class_arms', 'id')->where('tenant_id', $user->tenant_id)],
        ]);
        $student->update($data);
        return response()->json(['message' => 'Student status updated.', 'status' => $student->status]);
    }

    public function updateStaff(Request $request, User $member)
    {
        $user = $this->administrator($request);
        abort_unless((int) $member->tenant_id === (int) $user->tenant_id && $this->allows($user, 'staff'), 404);
        abort_if($member->id === $user->id, 422, 'You cannot deactivate your own account.');
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'], 'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($member->id)],
            'phone' => ['nullable', 'string', 'max:20'], 'role' => ['sometimes', Rule::in(User::staffRoleNames())],
        ]);
        if (isset($data['role'])) $data['role'] = User::canonicalRole($data['role']);
        $member->update($data);
        return response()->json(['message' => 'Staff account updated.', 'active' => (bool) $member->is_active]);
    }

    public function management(Request $request)
    {
        $user = $this->administrator($request);
        return response()->json([
            'class_levels' => ClassLevel::where('tenant_id', $user->tenant_id)->orderBy('order_index')->get(['id', 'name']),
            'classes' => ClassArm::where('tenant_id', $user->tenant_id)->with(['classLevel:id,name', 'formTutor:id,name'])->get()->map(fn ($arm) => [
                'id' => $arm->id, 'name' => $arm->name, 'full_name' => $arm->full_name,
                'class_level_id' => $arm->class_level_id, 'form_tutor_id' => $arm->form_tutor_id,
                'form_tutor' => $arm->formTutor?->name,
            ]),
            'subjects' => Subject::where('tenant_id', $user->tenant_id)->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'teachers' => User::where('tenant_id', $user->tenant_id)->where('is_active', true)
                ->whereIn('role', ['teacher', 'form_teacher', 'asst_form_teacher', 'subject_teacher', 'form_subject_teacher'])
                ->orderBy('name')->get(['id', 'name', 'role']),
            'staff_roles' => collect(User::staffRoleNames())->reject(fn ($role) => in_array($role, ['super_admin', 'student', 'parent'], true))->values(),
        ]);
    }

    public function storeStudent(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allows($user, 'students'), 403);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:male,female,other'], 'date_of_birth' => ['required', 'date', 'before:today'],
            'admission_date' => ['required', 'date'],
            'current_class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $user->tenant_id)],
        ]);
        $prefix = 'STU-'.now()->format('Y').'-';
        $last = Student::withoutTenantScope()->where('tenant_id', $user->tenant_id)->where('admission_number', 'like', $prefix.'%')->max('admission_number');
        $data['admission_number'] = $prefix.str_pad((string) (((int) Str::afterLast((string) $last, '-')) + 1), 4, '0', STR_PAD_LEFT);
        $data['tenant_id'] = $user->tenant_id; $data['status'] = Student::STATUS_ACTIVE;
        $student = Student::create($data);
        return response()->json(['message' => 'Student added.', 'student' => ['id' => $student->id, 'name' => $student->full_name]], 201);
    }

    public function storeStaff(Request $request)
    {
        $user = $this->administrator($request);
        abort_unless($this->allows($user, 'staff'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(User::staffRoleNames())], 'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        abort_if(in_array(User::canonicalRole($data['role']), ['student', 'parent', 'super_admin'], true), 422);
        $member = User::create([
            ...$data, 'tenant_id' => $user->tenant_id, 'password' => Hash::make($data['password']),
            'role' => User::canonicalRole($data['role']), 'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE, 'employment_started_at' => today(), 'status_changed_at' => now(),
        ]);
        $member->assignRole($member->role);
        return response()->json(['message' => 'Staff account added.', 'staff' => ['id' => $member->id, 'name' => $member->name]], 201);
    }

    public function storeClass(Request $request)
    {
        $user = $this->administrator($request); abort_unless($this->allows($user, 'classes'), 403);
        $data = $this->classData($request, $user);
        $arm = ClassArm::create([...$data, 'tenant_id' => $user->tenant_id]);
        return response()->json(['message' => 'Class added.', 'class' => ['id' => $arm->id, 'name' => $arm->full_name]], 201);
    }

    public function updateClass(Request $request, ClassArm $classArm)
    {
        $user = $this->administrator($request); abort_unless($classArm->tenant_id === $user->tenant_id && $this->allows($user, 'classes'), 404);
        $classArm->update($this->classData($request, $user));
        return response()->json(['message' => 'Class updated.']);
    }

    public function storeSubject(Request $request)
    {
        $user = $this->administrator($request); abort_unless($this->allows($user, 'subjects'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'code' => ['nullable', 'string', 'max:10'], 'is_active' => ['nullable', 'boolean']]);
        $subject = Subject::create([...$data, 'tenant_id' => $user->tenant_id, 'is_active' => $data['is_active'] ?? true]);
        return response()->json(['message' => 'Subject added.', 'subject' => $subject], 201);
    }

    public function updateSubject(Request $request, Subject $subject)
    {
        $user = $this->administrator($request); abort_unless($subject->tenant_id === $user->tenant_id && $this->allows($user, 'subjects'), 404);
        $subject->update($request->validate(['name' => ['sometimes', 'string', 'max:100'], 'code' => ['nullable', 'string', 'max:10'], 'is_active' => ['nullable', 'boolean']]));
        return response()->json(['message' => 'Subject updated.']);
    }

    private function classData(Request $request, User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $user->tenant_id)],
            'form_tutor_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $user->tenant_id)->whereIn('role', ['teacher', 'form_teacher', 'asst_form_teacher', 'form_subject_teacher']))],
        ]);
    }

    private function administrator(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user && in_array($user->roleKey(), self::ROLES, true), 403,
            'This portal is restricted to school administrators.');
        return $user;
    }

    private function allows(User $user, string $module): bool
    {
        $access = User::ROLE_ACCESS[$user->roleKey()] ?? [];
        return in_array('*', $access, true) || in_array($module, $access, true)
            || collect($access)->contains(fn ($permission) => str_starts_with($permission, $module.'.'));
    }

    private function allowsAny(User $user, array $modules): bool
    {
        return collect($modules)->contains(fn ($module) => $this->allows($user, $module));
    }
}
