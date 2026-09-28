<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassArm;
use App\Models\PublicStudentClassLink;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Scopes\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicStudentClassAssignmentController extends Controller
{
    public function index(Request $request, string $token)
    {
        $link = $this->resolve($token);
        return view('public.student-class-assignment', [
            'link' => $link,
            'classArm' => $link->classArm()->with('classLevel')->first(),
            'classes' => ClassArm::with('classLevel')
                ->where('tenant_id', $link->tenant_id)
                ->orderBy('class_level_id')
                ->orderBy('name')
                ->get(),
            'session' => $link->session,
            'term' => $link->term,
            'lookup' => $request->old('admission_numbers', ''),
            'selectedClassArmId' => $request->old('class_arm_id', $link->class_arm_id),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $link = $this->resolve($token);
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'admission_numbers' => ['required', 'string', 'max:5000'],
        ]);

        $numbers = collect(preg_split('/[\s,;]+/', strtoupper(trim($data['admission_numbers']))))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            throw ValidationException::withMessages(['admission_numbers' => 'Enter at least one admission number.']);
        }

        $tenantId = (int) $link->tenant_id;
        $assigned = 0;
        $skipped = [];
        $userAgent = substr((string) $request->userAgent(), 0, 1000);

        DB::transaction(function () use ($link, $tenantId, $numbers, $data, &$assigned, &$skipped, $request, $userAgent) {
            $classArm = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($data['class_arm_id'])
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($numbers as $number) {
                $student = Student::where('tenant_id', $tenantId)
                    ->whereRaw('UPPER(admission_number) = ?', [$number])
                    ->where('status', Student::STATUS_ACTIVE)
                    ->lockForUpdate()->first();

                if (!$student) { $skipped[] = $number . ' — student not found or inactive'; continue; }
                if ($student->current_class_arm_id) { $skipped[] = $number . ' — already assigned to a class'; continue; }

                $exists = StudentEnrollment::where('tenant_id', $tenantId)
                    ->where('student_id', $student->id)
                    ->where('session_id', $link->session_id)
                    ->where('term_id', $link->term_id)->exists();

                if ($exists) { $skipped[] = $number . ' — enrollment already exists'; continue; }

                if ($classArm->getAttribute('capacity')) {
                    $current = Student::where('tenant_id', $tenantId)->where('current_class_arm_id', $classArm->id)
                        ->where('status', Student::STATUS_ACTIVE)->lockForUpdate()->count();
                    if ($current >= (int) $classArm->getAttribute('capacity')) {
                        $skipped[] = $number . ' — destination class is at capacity';
                        continue;
                    }
                }

                $student->forceFill(['current_class_arm_id' => $classArm->id])->save();

                StudentEnrollment::create([
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'class_arm_id' => $classArm->id,
                    'session_id' => $link->session_id,
                    'term_id' => $link->term_id,
                    'start_date' => now()->toDateString(),
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
                    'old_values' => ['current_class_arm_id' => null],
                    'new_values' => ['student_id' => $student->id, 'class_arm_id' => $classArm->id],
                    'reason' => 'Assignment through public class link',
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                ]);
                $assigned++;
            }
        });

        return back()
            ->with('success', $assigned . ' student(s) assigned to ' . $this->classLabel($data['class_arm_id'], $tenantId) . '.')
            ->with('skipped', $skipped)
            ->withInput($request->only(['class_arm_id', 'admission_numbers']));
    }

    private function classLabel(int $classArmId, int $tenantId): string
    {
        $class = ClassArm::with('classLevel')
            ->where('tenant_id', $tenantId)
            ->whereKey($classArmId)
            ->first();
        return $class?->full_name ?? 'the selected class';
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
        $links = PublicStudentClassLink::with(['classArm.classLevel','session','term'])
            ->where('tenant_id', $tenantId)->latest()->paginate(15);
        $classes = ClassArm::with('classLevel')->where('tenant_id', $tenantId)->orderBy('class_level_id')->orderBy('name')->get();
        $sessions = AcademicSession::where('tenant_id', $tenantId)->where('is_current', true)->get();
        return view('students.public-class-links', compact('links','classes','sessions'));
    }

    public function generate(Request $request)
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $data = $request->validate([
            'expires_in_days' => ['required','integer','in:1,3,7,14,30'],
        ]);
        $context = $this->activeContext($tenantId);

        $token = Str::random(64);
        PublicStudentClassLink::create([
            'tenant_id'=>$tenantId,
            'class_arm_id'=>null,
            'session_id'=>$context['session']->id,
            'term_id'=>$context['term']->id,
            'token_hash'=>hash('sha256',$token),
            'expires_at'=>now()->addDays((int)$data['expires_in_days']),
            'created_by'=>auth()->id(),
        ]);

        return back()->with('public_link', route('public.student-class-assignment', ['token'=>$token]));
    }

    public function revoke(PublicStudentClassLink $link)
    {
        abort_unless((int)$link->tenant_id === (int)auth()->user()->tenant_id, 404);
        $link->update(['revoked_at'=>now()]);
        return back()->with('success','Public class assignment link revoked.');
    }

    private function activeContext(int $tenantId): array
    {
        $session = AcademicSession::where('tenant_id',$tenantId)->where('is_current',true)->first();
        $term = $session ? Term::where('tenant_id',$tenantId)->where('session_id',$session->id)->where('is_current',true)->first() : null;
        if (!$session || !$term) abort(422, 'An active academic session and term are required.');
        return compact('session','term');
    }
}
