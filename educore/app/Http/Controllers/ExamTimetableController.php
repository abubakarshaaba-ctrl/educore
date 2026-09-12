<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ExamSchedule;
use App\Models\ExamSupervision;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamTimetableController extends Controller
{
    private const ACADEMIC_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'head_of_schools',
        'vice_principal', 'academic_administrator', 'director_of_studies',
        'hod', 'head_of_department', 'teacher', 'subject_teacher', 'class_teacher',
        'form_teacher', 'asst_form_teacher', 'form_subject_teacher',
    ];

    private const MANAGER_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'head_of_schools',
        'vice_principal', 'academic_administrator', 'director_of_studies',
    ];

    public function index(Request $request)
    {
        $user = $this->guardAcademic($request);
        $tenantId = (int) $user->tenant_id;
        $currentTerm = Term::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $termId = $request->integer('term_id') ?: $currentTerm?->id;

        $query = ExamSchedule::with(['session', 'term', 'classArm.classLevel', 'subject', 'supervisions.staff'])
            ->where('tenant_id', $tenantId)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->when($request->filled('class_arm_id'), fn ($q) => $q->where('class_arm_id', $request->integer('class_arm_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('exam_date', $request->date('date')))
            ->orderBy('exam_date')->orderBy('start_time');

        $schedules = $query->get()->groupBy(fn (ExamSchedule $row) => $row->exam_date->toDateString());
        $terms = Term::where('tenant_id', $tenantId)->with('session')->orderByDesc('start_date')->get();
        $classes = ClassArm::where('tenant_id', $tenantId)->with('classLevel')->get()
            ->sortBy(fn ($c) => trim(($c->classLevel?->name ?? '').' '.$c->name))->values();
        $subjects = Subject::where('tenant_id', $tenantId)->orderBy('name')->get();
        $canManage = $this->canManage($user);
        $staff = $canManage ? User::activeStaff($tenantId)->orderBy('name')->get() : collect();

        return view('exam-timetable.index', compact(
            'schedules', 'terms', 'classes', 'subjects', 'staff', 'currentTerm', 'termId', 'canManage'
        ));
    }

    public function mySupervision(Request $request)
    {
        $user = $this->guardAcademic($request);
        $tenantId = (int) $user->tenant_id;
        $currentTerm = Term::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $termId = $request->integer('term_id') ?: $currentTerm?->id;

        $query = ExamSupervision::with(['examSchedule.session', 'examSchedule.term', 'examSchedule.classArm.classLevel', 'examSchedule.subject'])
            ->where('tenant_id', $tenantId)
            ->where('staff_id', $user->id)
            ->whereHas('examSchedule', function ($q) use ($tenantId, $termId, $request): void {
                $q->where('tenant_id', $tenantId)
                    ->when($termId, fn ($inner) => $inner->where('term_id', $termId));
                if ($request->input('scope', 'upcoming') === 'upcoming') {
                    $q->whereDate('exam_date', '>=', today());
                }
            })
            ->get()
            ->sortBy(fn (ExamSupervision $row) => $row->examSchedule->exam_date->format('Y-m-d').' '.$row->examSchedule->start_time)
            ->groupBy(fn (ExamSupervision $row) => $row->examSchedule->exam_date->toDateString());

        $terms = Term::where('tenant_id', $tenantId)->with('session')->orderByDesc('start_date')->get();
        $nextDuty = $query->flatten()->first();

        return view('exam-timetable.my-supervision', [
            'duties' => $query,
            'terms' => $terms,
            'termId' => $termId,
            'currentTerm' => $currentTerm,
            'nextDuty' => $nextDuty,
            'scope' => $request->input('scope', 'upcoming'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guardManager($request);
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSchedule($request, $tenantId);
        $this->assertScheduleAvailable($tenantId, $data);

        ExamSchedule::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'created_by' => $user->id,
        ]));

        return back()->with('success', 'Exam timetable entry created.');
    }

    public function update(Request $request, ExamSchedule $examSchedule)
    {
        $user = $this->guardManager($request);
        $this->assertTenantSchedule($examSchedule, (int) $user->tenant_id);
        $data = $this->validateSchedule($request, (int) $user->tenant_id);
        $this->assertScheduleAvailable((int) $user->tenant_id, $data, $examSchedule->id);
        $examSchedule->update($data);
        return back()->with('success', 'Exam timetable entry updated.');
    }

    public function destroy(Request $request, ExamSchedule $examSchedule)
    {
        $user = $this->guardManager($request);
        $this->assertTenantSchedule($examSchedule, (int) $user->tenant_id);
        $examSchedule->delete();
        return back()->with('success', 'Exam timetable entry deleted.');
    }

    public function assignSupervisor(Request $request, ExamSchedule $examSchedule)
    {
        $user = $this->guardManager($request);
        $tenantId = (int) $user->tenant_id;
        $this->assertTenantSchedule($examSchedule, $tenantId);
        $data = $request->validate([
            'staff_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('is_active', true))],
            'role' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = User::activeStaff($tenantId)->whereKey($data['staff_id'])->firstOrFail();
        abort_unless($this->isAcademic($staff), 422, 'Only academic staff can be assigned examination supervision.');

        $conflict = ExamSupervision::where('tenant_id', $tenantId)
            ->where('staff_id', $staff->id)
            ->whereHas('examSchedule', fn ($q) => $q
                ->whereDate('exam_date', $examSchedule->exam_date)
                ->where('start_time', '<', $examSchedule->end_time)
                ->where('end_time', '>', $examSchedule->start_time)
                ->whereKeyNot($examSchedule->id))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['staff_id' => 'This staff member already has an overlapping supervision duty.']);
        }

        ExamSupervision::updateOrCreate(
            ['tenant_id' => $tenantId, 'exam_schedule_id' => $examSchedule->id, 'staff_id' => $staff->id],
            ['role' => $data['role'] ?: 'Invigilator', 'notes' => $data['notes'] ?? null]
        );

        return back()->with('success', 'Supervisor assigned.');
    }

    public function removeSupervisor(Request $request, ExamSchedule $examSchedule, ExamSupervision $supervision)
    {
        $user = $this->guardManager($request);
        $tenantId = (int) $user->tenant_id;
        $this->assertTenantSchedule($examSchedule, $tenantId);
        abort_unless($supervision->tenant_id === $tenantId && $supervision->exam_schedule_id === $examSchedule->id, 404);
        $supervision->delete();
        return back()->with('success', 'Supervisor removed.');
    }

    private function validateSchedule(Request $request, int $tenantId): array
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'class_arm_id' => ['required', 'integer', Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'title' => ['nullable', 'string', 'max:120'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $term = Term::where('tenant_id', $tenantId)->findOrFail($data['term_id']);
        abort_unless((int) $term->session_id === (int) $data['session_id'], 422, 'The selected term does not belong to the selected academic session.');
        if ($term->start_date && $term->end_date) {
            $date = \Carbon\Carbon::parse($data['exam_date']);
            if ($date->lt($term->start_date) || $date->gt($term->end_date)) {
                throw ValidationException::withMessages(['exam_date' => 'Exam date must fall within the selected term.']);
            }
        }
        $data['start_time'] .= ':00';
        $data['end_time'] .= ':00';
        return $data;
    }

    private function assertScheduleAvailable(int $tenantId, array $data, ?int $ignoreId = null): void
    {
        $overlap = ExamSchedule::where('tenant_id', $tenantId)
            ->whereDate('exam_date', $data['exam_date'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

        if ((clone $overlap)->where('class_arm_id', $data['class_arm_id'])->exists()) {
            throw ValidationException::withMessages(['start_time' => 'This class already has an examination during the selected time.']);
        }
        if (!empty($data['venue']) && (clone $overlap)->where('venue', $data['venue'])->exists()) {
            throw ValidationException::withMessages(['venue' => 'This venue is already in use during the selected time.']);
        }
    }

    private function guardAcademic(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && $user->isTenantStaff(), 403);
        abort_if($user->hasDeniedPermission('exam-timetable'), 403, 'Exam timetable access has been denied for this account.');
        abort_unless($this->isAcademic($user) || $user->hasGrantedPermission('exam-timetable'), 403, 'Academic staff access required.');
        return $user;
    }

    private function guardManager(Request $request): User
    {
        $user = $this->guardAcademic($request);
        abort_unless($this->canManage($user), 403, 'Exam timetable management permission required.');
        return $user;
    }

    private function canManage(User $user): bool
    {
        return in_array(strtolower((string) $user->roleKey()), self::MANAGER_ROLES, true)
            || $user->hasGrantedPermission('exam-timetable.manage');
    }

    private function isAcademic(User $user): bool
    {
        $roles = collect($user->getRoleNames())
            ->push($user->roleKey())
            ->map(fn ($role) => strtolower((string) $role));
        return $roles->contains(fn ($role) => in_array($role, self::ACADEMIC_ROLES, true));
    }

    private function assertTenantSchedule(ExamSchedule $schedule, int $tenantId): void
    {
        abort_unless((int) $schedule->tenant_id === $tenantId, 404);
    }
}
