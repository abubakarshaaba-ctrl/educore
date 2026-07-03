<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\Subject;
use App\Models\SubjectFrequency;
use App\Models\TimetableConfig;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Services\TimetableGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function tenantTeacherRule()
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->tenantId())
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
            ->whereIn('role', User::teachingRoleNames()));
    }

    // ---------------------------------------------------------------
    // STEP 1 — SCHOOL HOURS CONFIGURATION
    // ---------------------------------------------------------------
    public function configure()
    {
        abort_unless(auth()->user()->canManage('timetable'), 403,
            'Only administrators can configure school timetable settings.');
        $sessions = AcademicSession::orderByDesc('is_current')->get();
        $configs  = TimetableConfig::with('session')->get()->keyBy('session_id');
        return view('timetable.configure', compact('sessions', 'configs'));
    }

    public function saveConfig(Request $request)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $validated = $request->validate([
            'session_id'      => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'school_start'    => ['required', 'date_format:H:i'],
            'school_end'      => ['required', 'date_format:H:i', 'after:school_start'],
            'periods_per_day' => ['required', 'integer', 'min:1', 'max:12'],
            'period_duration' => ['required', 'integer', 'min:20', 'max:120'],
            'breaks'          => ['nullable', 'array'],
            'breaks.*.after_period' => ['required', 'integer', 'min:1'],
            'breaks.*.duration'     => ['required', 'integer', 'min:5', 'max:60'],
            'breaks.*.label'        => ['required', 'string', 'max:50'],
        ]);

        // Validate total time fits
        $totalMins  = $validated['periods_per_day'] * $validated['period_duration'];
        $breakMins  = collect($validated['breaks'] ?? [])->sum('duration');
        $available  = $this->timeDiffMins($validated['school_start'], $validated['school_end']);

        if (($totalMins + $breakMins) > $available) {
            return back()->withErrors(['periods_per_day' => 
                "Total time needed (" . ($totalMins + $breakMins) . " mins) exceeds school hours ({$available} mins). " .
                "Reduce periods, period duration or break time."
            ]);
        }

        TimetableConfig::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'session_id' => $validated['session_id']],
            [
                'school_start'    => $validated['school_start'],
                'school_end'      => $validated['school_end'],
                'periods_per_day' => $validated['periods_per_day'],
                'period_duration' => $validated['period_duration'],
                'breaks'          => $validated['breaks'] ?? [],
            ]
        );

        return back()->with('success', 'School timetable configuration saved.');
    }

    // ---------------------------------------------------------------
    // STEP 2 — SUBJECT FREQUENCY SETUP
    // ---------------------------------------------------------------
    public function frequency()
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $classArms = ClassArm::with('classLevel')->get();
        $sessions  = AcademicSession::orderByDesc('is_current')->get();
        return view('timetable.frequency', compact('classArms', 'sessions'));
    }

    public function loadFrequency(Request $request)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'session_id'   => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm    = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $session     = AcademicSession::findOrFail($request->session_id);
        $classArms   = ClassArm::with('classLevel')->get();
        $sessions    = AcademicSession::orderByDesc('is_current')->get();

        // Subjects assigned to this class
        $assignments = ClassArmSubject::where('class_arm_id', $classArm->id)
                                      ->where('session_id', $session->id)
                                      ->with('subject', 'teacher')
                                      ->get();

        // Existing frequencies
        $frequencies = SubjectFrequency::where('class_arm_id', $classArm->id)
                                       ->where('session_id', $session->id)
                                       ->get()
                                       ->keyBy('subject_id');

        // Config for this session
        $config = TimetableConfig::where('session_id', $session->id)->first();

        return view('timetable.frequency', compact(
            'classArm', 'session', 'classArms', 'sessions',
            'assignments', 'frequencies', 'config'
        ));
    }

    public function saveFrequency(Request $request)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $request->validate([
            'class_arm_id'      => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'session_id'        => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'frequencies'       => ['required', 'array'],
            'frequencies.*'     => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $allowedSubjectIds = ClassArmSubject::where('class_arm_id', $request->class_arm_id)
            ->where('session_id', $request->session_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($request->frequencies as $subjectId => $freq) {
            abort_unless(in_array((int) $subjectId, $allowedSubjectIds, true), 403);

            SubjectFrequency::updateOrCreate(
                [
                    'tenant_id'    => $tenantId,
                    'class_arm_id' => $request->class_arm_id,
                    'subject_id'   => $subjectId,
                    'session_id'   => $request->session_id,
                ],
                ['periods_per_week' => (int)$freq]
            );
        }

        return back()->with('success', 'Subject frequencies saved successfully.');
    }

    // ---------------------------------------------------------------
    // STEP 3 — AUTO-GENERATE
    // ---------------------------------------------------------------
    public function generate(Request $request)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'session_id'   => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'overwrite'    => ['boolean'],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $session  = AcademicSession::findOrFail($request->session_id);

        $result = (new TimetableGeneratorService())->generate(
            classArmId: $classArm->id,
            sessionId:  $session->id,
            tenantId:   auth()->user()->tenant_id,
            overwrite:  $request->boolean('overwrite', true),
        );

        $message = "{$result['created']} periods generated for {$classArm->classLevel->name} {$classArm->name}.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} period(s) skipped due to clashes.";
        }
        if ($result['created'] === 0) {
            $message = $result['conflicts'][0] ?? 'No periods could be generated.';
        }

        return redirect()->route('timetable.view', [
            'class_arm_id' => $classArm->id,
            'session_id'   => $session->id,
        ])->with('success', $message)
          ->with('tt_conflicts', $result['conflicts']);
    }

    // ---------------------------------------------------------------
    // VIEW — Weekly timetable grid
    // ---------------------------------------------------------------
    public function index()
    {
        $user      = auth()->user();
        $sessions  = AcademicSession::orderByDesc('is_current')->get();
        $currentSessionId = AcademicSession::where('tenant_id', $this->tenantId())
            ->where('is_current', true)->value('id');

        // For non-admin staff, determine what they're assigned to
        $isAdminTier   = $user->canManage('timetable') || $user->isSuperAdmin();
        $isFormTeacher = !$isAdminTier && $user->hasFormTeacherDuty();
        $isSubjTeacher = !$isAdminTier && $user->hasSubjectTeacherDuty();

        if (!$isAdminTier) {
            // Try to find their form class first
            $myClassArm = ClassArm::where('form_tutor_id', $user->id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if ($myClassArm && $currentSessionId) {
                // Has a form class → go to class timetable
                return redirect()->route('timetable.view', [
                    'class_arm_id' => $myClassArm->id,
                    'session_id'   => $currentSessionId,
                ]);
            }

            // No form class — fall back to their subject schedule
            if ($isSubjTeacher) {
                return redirect()->route('timetable.teacher', [
                    'teacher_id' => $user->id,
                    'session_id' => $currentSessionId,
                ]);
            }
        }

        // Admin / full timetable access → full index.
        $classArms = ClassArm::with('classLevel')->get();
        return view('timetable.index', compact('classArms', 'sessions'));
    }

    public function view(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'session_id'   => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm  = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);

        // Form teachers may only view the timetable for their own assigned class.
        $user = auth()->user();
        if ($user->hasFormTeacherDuty() && !$user->canManage('timetable')) {
            if ($classArm->form_tutor_id !== $user->id) {
                $myArm = ClassArm::where('form_tutor_id', $user->id)
                    ->where('tenant_id', $this->tenantId())->first();
                if ($myArm) {
                    return redirect()->route('timetable.view', [
                        'class_arm_id' => $myArm->id,
                        'session_id'   => $request->session_id,
                    ]);
                }
                abort(403, 'You can only view the timetable for your assigned class.');
            }
        }

        $session   = AcademicSession::findOrFail($request->session_id);

        // Scoped users see only their own class in the selector.
        $isFormScoped = $user->hasFormTeacherDuty() && !$user->canManage('timetable');
        $classArms = $isFormScoped
            ? ClassArm::with('classLevel')->where('form_tutor_id', $user->id)->get()
            : ClassArm::with('classLevel')->get();
        $sessions  = AcademicSession::orderByDesc('is_current')->get();
        $subjects  = Subject::where('is_active', true)->get();
        $teachers  = User::activeStaff($this->tenantId())->teachers()->orderBy('name')->get();
        $config    = TimetableConfig::where('session_id', $session->id)->first();

        // Compute time slots from config
        $allSlots = $config ? $config->computeSlots() : [];

        $days    = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $periods = TimetablePeriod::where('class_arm_id', $classArm->id)
                                  ->where('session_id', $session->id)
                                  ->with(['subject', 'teacher'])
                                  ->get()
                                  ->groupBy('day_of_week');

        return view('timetable.view', compact(
            'classArm', 'session', 'classArms', 'sessions',
            'subjects', 'teachers', 'days', 'periods', 'config', 'allSlots'
        ));
    }

    // ---------------------------------------------------------------
    // MANUAL PERIOD ADD/DELETE
    // ---------------------------------------------------------------
    public function store(Request $request)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $validated = $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'session_id'   => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'subject_id'   => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
            'teacher_id'   => ['nullable', $this->tenantTeacherRule()],
            'day_of_week'  => ['required', 'in:monday,tuesday,wednesday,thursday,friday'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'venue'        => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['teacher_id']) {
            $clash = TimetablePeriod::where('teacher_id', $validated['teacher_id'])
                ->where('day_of_week', $validated['day_of_week'])
                ->where('session_id', $validated['session_id'])
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

            if ($clash) {
                return back()->withErrors(['teacher_id' => 'This teacher already has a class at this time.']);
            }
        }

        TimetablePeriod::create($validated);
        return back()->with('success', 'Period added.');
    }

    public function destroy(TimetablePeriod $period)
    {
        abort_unless(auth()->user()->canManage('timetable'), 403);
        $classArmId = $period->class_arm_id;
        $sessionId  = $period->session_id;
        $period->delete();
        return redirect()->route('timetable.view', [
            'class_arm_id' => $classArmId,
            'session_id'   => $sessionId,
        ])->with('success', 'Period removed.');
    }

    // ---------------------------------------------------------------
    // TEACHER TIMETABLE
    // ---------------------------------------------------------------
    public function teacher(Request $request)
    {
        $user = auth()->user();
        $isScoped = $user->hasSubjectTeacherDuty() && !$user->canManage('timetable');

        // Scoped teachers can only view their own schedule.
        if ($isScoped) {
            $teachers = collect([$user]);
            // Auto-fill their own ID if not set.
            if (!$request->filled('teacher_id')) {
                $request->merge(['teacher_id' => $user->id]);
            } elseif ((int) $request->teacher_id !== $user->id) {
                // Prevent viewing other teachers' schedules.
                return redirect()->route('timetable.teacher', [
                    'teacher_id' => $user->id,
                    'session_id' => $request->session_id,
                ]);
            }
        } else {
            $teachers = User::activeStaff($this->tenantId())->teachers()->orderBy('name')->get();
        }

        $sessions = AcademicSession::orderByDesc('is_current')->get();

        if (!$request->filled('teacher_id') || !$request->filled('session_id')) {
            return view('timetable.teacher', compact('teachers', 'sessions'));
        }

        $request->validate([
            'teacher_id' => ['required', $this->tenantTeacherRule()],
            'session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $teacher = User::activeStaff($this->tenantId())->teachers()->findOrFail($request->teacher_id);
        $session = AcademicSession::findOrFail($request->session_id);
        $days    = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $config  = TimetableConfig::where('session_id', $session->id)->first();

        $periods = TimetablePeriod::where('teacher_id', $teacher->id)
                                  ->where('session_id', $session->id)
                                  ->with(['subject', 'classArm.classLevel'])
                                  ->get()
                                  ->groupBy('day_of_week');

        $allSlots = $config ? $config->computeSlots() : [];

        return view('timetable.teacher', compact(
            'teachers', 'sessions', 'teacher', 'session', 'days', 'periods', 'allSlots'
        ));
    }

    // Helper
    private function timeDiffMins(string $start, string $end): int
    {
        [$sh, $sm] = explode(':', $start);
        [$eh, $em] = explode(':', $end);
        return ((int)$eh * 60 + (int)$em) - ((int)$sh * 60 + (int)$sm);
    }

    public function conflicts(\Illuminate\Http\Request $request)
    {
        $session = \App\Models\AcademicSession::where('tenant_id', $this->tenantId())
            ->where('is_current', true)->first();
        $conflicts = [];

        $periods = \App\Models\TimetablePeriod::where('session_id', $session?->id)
            ->whereNotNull('teacher_id')
            ->with(['classArm.classLevel'])
            ->get();

        // Group by teacher + day, then check every pair for time overlap
        $byTeacherDay = $periods->groupBy(fn($p) => $p->teacher_id . '|' . $p->day_of_week);

        foreach ($byTeacherDay as $key => $group) {
            [$teacherId, $day] = array_pad(explode('|', $key, 2), 2, '');
            if ($group->count() < 2) continue;

            $items = $group->values();
            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    $a = $items[$i];
                    $b = $items[$j];
                    // Two periods overlap when: a.start < b.end AND a.end > b.start
                    if ($a->start_time < $b->end_time && $a->end_time > $b->start_time) {
                        $teacher = \App\Models\User::where('tenant_id', $this->tenantId())->find($teacherId);
                        $conflicts[] = [
                            'teacher'    => $teacher?->name ?? 'Unknown',
                            'day'        => ucfirst($day),
                            'start_time' => $a->start_time . ' – ' . $a->end_time,
                            'classes'    => collect([$a, $b])->map(fn($p) =>
                                optional(optional($p->classArm)->classLevel)->name . ' ' . optional($p->classArm)->name
                                . ' (' . $p->start_time . '-' . $p->end_time . ')'
                            )->join(' vs '),
                            'count'      => 2,
                        ];
                    }
                }
            }
        }

        return view('timetable.conflicts', compact('conflicts'));
    }

}
