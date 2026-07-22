<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;

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
        $data = $request->validate(['status' => ['required', 'in:active,inactive,graduated,transferred,withdrawn']]);
        $student->update($data);
        return response()->json(['message' => 'Student status updated.', 'status' => $student->status]);
    }

    public function updateStaff(Request $request, User $member)
    {
        $user = $this->administrator($request);
        abort_unless((int) $member->tenant_id === (int) $user->tenant_id && $this->allows($user, 'staff'), 404);
        abort_if($member->id === $user->id, 422, 'You cannot deactivate your own account.');
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $member->update($data);
        return response()->json(['message' => 'Staff account updated.', 'active' => (bool) $member->is_active]);
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
