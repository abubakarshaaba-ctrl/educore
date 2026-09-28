<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassArm;
use App\Models\PublicStudentClassLink;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Tenant;
use App\Models\Guardian;
use App\Models\Scopes\TenantContext;
use App\Services\PlanLimitService;
use App\Services\StudentIdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicStudentClassAssignmentController extends Controller
{
    public function __construct(private readonly StudentIdGenerator $studentIdGenerator) {}

    public function index(Request $request, string $token)
    {
        $link = $this->resolve($token);

        return view('public.student-class-admission', [
            'link' => $link,
            'tenant' => Tenant::withoutGlobalScopes()->findOrFail((int) $link->tenant_id),
            'classArm' => $link->classArm()->with('classLevel')->first(),
            'classes' => ClassArm::with('classLevel')->where('tenant_id', $link->tenant_id)->orderBy('class_level_id')->orderBy('name')->get(),
            'session' => $link->session,
            'term' => $link->term,
            'geo' => \App\Data\NigeriaGeo::all(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $link = $this->resolve($token);
        $tenantId = (int) $link->tenant_id;

        $data = $request->validate([
            'guardian_first_name' => ['required', 'string', 'max:100'],
            'guardian_last_name' => ['required', 'string', 'max:100'],
            'guardian_phone' => ['required', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
            'guardian_relationship' => ['required', 'in:father,mother,guardian,other'],
            'students' => ['required', 'array', 'min:1', 'max:20'],
            'students.*.first_name' => ['required', 'string', 'max:100'],
            'students.*.last_name' => ['required', 'string', 'max:100'],
            'students.*.middle_name' => ['nullable', 'string', 'max:100'],
            'students.*.gender' => ['required', 'in:male,female,other'],
            'students.*.date_of_birth' => ['required', 'date', 'before:today'],
            'students.*.current_class_arm_id' => ['required', 'integer', Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId)],
            'students.*.admission_date' => ['required', 'date'],
            'students.*.state_of_origin' => ['nullable', 'string', 'max:100'],
            'students.*.lga_of_origin' => ['nullable', 'string', 'max:100'],
            'students.*.religion' => ['nullable', 'string', 'max:50'],
            'students.*.blood_group' => ['nullable', 'string', 'max:5'],
            'students.*.genotype' => ['nullable', 'string', 'max:5'],
        ]);

        // PublicStudentClassLink intentionally has no tenant() relationship.
        // Resolve the tenant directly so submission does not fail with a 500.
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $studentCount = count($data['students']);
        $remaining = PlanLimitService::remainingStudentSlots($tenant);

        if ($remaining < $studentCount) {
            throw ValidationException::withMessages([
                'students' => "This submission contains {$studentCount} students, but only {$remaining} student slot(s) remain in the school's current capacity.",
            ]);
        }

        $created = [];
        $userAgent = substr((string) $request->userAgent(), 0, 1000);

        DB::transaction(function () use ($link, $tenantId, $data, &$created, $request, $userAgent) {
            $guardian = Guardian::query()->create([
                'tenant_id' => $tenantId,
                'first_name' => $data['guardian_first_name'],
                'last_name' => $data['guardian_last_name'],
                'phone' => $data['guardian_phone'],
                'email' => $data['guardian_email'] ?? null,
                'relationship' => $data['guardian_relationship'],
            ]);

            foreach ($data['students'] as $studentData) {
                $classArm = ClassArm::where('tenant_id', $tenantId)->whereKey($studentData['current_class_arm_id'])->lockForUpdate()->firstOrFail();

                if ($classArm->getAttribute('capacity')) {
                    $current = Student::where('tenant_id', $tenantId)->where('current_class_arm_id', $classArm->id)->where('status', Student::STATUS_ACTIVE)->lockForUpdate()->count();
                    if ($current >= (int) $classArm->getAttribute('capacity')) {
                        throw ValidationException::withMessages([
                            'students' => 'The selected class "' . $classArm->full_name . '" is at capacity. No students from this submission were admitted.',
                        ]);
                    }
                }

                $admissionNumber = $this->studentIdGenerator->generate();

                $student = Student::create([
                    'tenant_id' => $tenantId,
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'middle_name' => $studentData['middle_name'] ?? null,
                    'gender' => $studentData['gender'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'current_class_arm_id' => $classArm->id,
                    'admission_date' => $studentData['admission_date'],
                    'admission_number' => $admissionNumber,
                    'state_of_origin' => $studentData['state_of_origin'] ?? null,
                    'lga_of_origin' => $studentData['lga_of_origin'] ?? null,
                    'religion' => $studentData['religion'] ?? null,
                    'blood_group' => $studentData['blood_group'] ?? null,
                    'genotype' => $studentData['genotype'] ?? null,
                    'status' => Student::STATUS_ACTIVE,
                ]);

                $student->guardians()->attach($guardian->id, ['tenant_id' => $tenantId, 'is_primary_contact' => true]);

                StudentEnrollment::create([
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'class_arm_id' => $classArm->id,
                    'session_id' => $link->session_id,
                    'term_id' => $link->term_id,
                    'start_date' => $studentData['admission_date'],
                    'end_date' => null,
                    'is_current' => true,
                    'status' => StudentEnrollment::STATUS_ACTIVE,
                    'created_by' => null,
                ]);

                $student->syncCompulsorySubjects($link->session_id);

                AuditLog::create([
                    'tenant_id' => $tenantId,
                    'actor_user_id' => null,
                    'auditable_type' => PublicStudentClassLink::class,
                    'auditable_id' => $link->id,
                    'action' => 'public_student_class_assignment.completed',
                    'old_values' => ['student_id' => null],
                    'new_values' => ['student_id' => $student->id, 'admission_number' => $student->admission_number, 'class_arm_id' => $classArm->id, 'guardian_id' => $guardian->id],
                    'reason' => 'New student admission through public reusable class link',
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                ]);

                $created[] = ['name' => $student->full_name, 'admission_number' => $student->admission_number, 'class' => $classArm->full_name];
            }
        });

        return redirect()->route('public.student-class-assignment', ['token' => $token])
            ->with('success', 'Student admission completed successfully.')
            ->with('created_students', $created);
    }

    private function resolve(string $token): PublicStudentClassLink
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40,100}$/', $token), 404);
        $link = PublicStudentClassLink::withoutTenantScope()->where('token_hash', hash('sha256', $token))->first();
        abort_unless($link && $link->isUsable(), 404);
        TenantContext::set((int) $link->tenant_id);
        return $link;
    }

    public function manage()
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $links = PublicStudentClassLink::with(['classArm.classLevel', 'session', 'term'])->where('tenant_id', $tenantId)->latest()->paginate(15);
        $classes = ClassArm::with('classLevel')->where('tenant_id', $tenantId)->orderBy('class_level_id')->orderBy('name')->get();
        $sessions = AcademicSession::where('tenant_id', $tenantId)->where('is_current', true)->get();
        return view('students.public-class-links', compact('links', 'classes', 'sessions'));
    }

    public function generate(Request $request)
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $data = $request->validate(['expires_in_days' => ['required', 'integer', 'in:1,3,7,14,30']]);
        $context = $this->activeContext($tenantId);
        $token = Str::random(64);

        PublicStudentClassLink::create([
            'tenant_id' => $tenantId,
            'class_arm_id' => null,
            'session_id' => $context['session']->id,
            'term_id' => $context['term']->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) $data['expires_in_days']),
            'created_by' => auth()->id(),
        ]);

        return back()->with('public_link', route('public.student-class-assignment', ['token' => $token]));
    }

    public function revoke(PublicStudentClassLink $link)
    {
        abort_unless((int) $link->tenant_id === (int) auth()->user()->tenant_id, 404);
        $link->update(['revoked_at' => now()]);
        return back()->with('success', 'Public class assignment link revoked.');
    }

    private function activeContext(int $tenantId): array
    {
        $session = AcademicSession::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $term = $session ? Term::where('tenant_id', $tenantId)->where('session_id', $session->id)->where('is_current', true)->first() : null;
        if (!$session || !$term) abort(422, 'An active academic session and term are required.');
        return compact('session', 'term');
    }
}
