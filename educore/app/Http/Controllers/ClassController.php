<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\PromotionRule;
use App\Models\Subject;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function tenantUserRule()
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->tenantId())
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
            ->whereIn('role', User::teachingRoleNames()));
    }

    private function normaliseClassLevelIds(Request $request): array
    {
        $ids = collect($request->input('class_level_ids', []));
        if ($request->filled('class_level_id')) {
            $ids->push($request->input('class_level_id'));
        }

        return $ids->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function buildBulkPromotionPlan(array $operation): array
    {
        $fromArm = ClassArm::with('classLevel')->find((int) $operation['from_class_arm_id']);
        $toArm = ClassArm::with('classLevel')->find((int) $operation['to_class_arm_id']);
        $term = \App\Models\Term::with('session')->find((int) $operation['term_id']);

        if (!$fromArm || !$toArm || !$term) {
            return ['error' => 'One or more selected class/term records no longer exist.'];
        }

        if ($fromArm->id === $toArm->id) {
            return ['error' => "{$fromArm->classLevel->name} {$fromArm->name}: source and destination classes cannot be the same."];
        }

        $nextLevel = ClassLevel::where('tenant_id', $this->tenantId())
            ->where('order_index', '>', $fromArm->classLevel->order_index)
            ->orderBy('order_index')
            ->first();

        if (!$nextLevel) {
            return ['error' => "{$fromArm->classLevel->name} is a terminal class level. Use the graduation workflow instead of assigning a higher class manually."];
        }

        if ((int) $toArm->class_level_id !== (int) $nextLevel->id) {
            return ['error' => "Invalid destination for {$fromArm->classLevel->name}. The next configured class level is {$nextLevel->name}."];
        }

        $studentsQuery = \App\Models\Student::where('current_class_arm_id', $fromArm->id)
            ->where('status', \App\Models\Student::STATUS_ACTIVE);

        $explicitStudentIds = collect($operation['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();
        if ($explicitStudentIds->isNotEmpty()) {
            $studentsQuery->whereIn('id', $explicitStudentIds);
        }

        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get();
        $summaries = \App\Models\TermlySummary::where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $minimumAverage = (float) ($operation['min_average'] ?? 0);
        $eligible = [];
        $blocked = [];

        foreach ($students as $student) {
            $summary = $summaries->get($student->id);
            if (!$summary) {
                $blocked[] = [
                    'student' => $student,
                    'summary' => null,
                    'reason' => 'No report card/termly summary has been computed for the selected term.',
                ];
                continue;
            }

            if ((float) $summary->final_average < $minimumAverage) {
                $blocked[] = [
                    'student' => $student,
                    'summary' => $summary,
                    'reason' => "Average {$summary->final_average}% is below the {$minimumAverage}% threshold.",
                ];
                continue;
            }

            $eligible[] = ['student' => $student, 'summary' => $summary];
        }

        $sessionId = \App\Models\AcademicSession::where('tenant_id', $this->tenantId())
            ->where('is_current', true)
            ->value('id') ?: $term->session_id;

        if (!$sessionId) {
            return ['error' => 'No current academic session is configured and the selected term is not linked to a session.'];
        }

        return [
            'fromArm' => $fromArm,
            'toArm' => $toArm,
            'term' => $term,
            'session_id' => (int) $sessionId,
            'minimum_average' => $minimumAverage,
            'eligible' => $eligible,
            'blocked' => $blocked,
            'eligible_count' => count($eligible),
            'blocked_count' => count($blocked),
        ];
    }

    // ── Class Levels ─────────────────────────────────────────────────
    public function levels()
    {
        $levels = ClassLevel::with(['classArms.students', 'gradingSystems', 'promotionRule'])
                            ->orderBy('order_index')->get();

        $totalStudents = \App\Models\Student::where('status', \App\Models\Student::STATUS_ACTIVE)->count();
        $totalArms     = \App\Models\ClassArm::count();
        $totalLevels   = $levels->count();

        return view('classes.levels', compact('levels', 'totalStudents', 'totalArms', 'totalLevels'));
    }

    public function storeLevel(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:80'],
            'order_index' => ['nullable','integer'],
            'section'     => ['required','in:creche,nursery,primary,junior_secondary,senior_secondary'],
        ]);
        ClassLevel::create($data);
        return back()->with('success', 'Class level created.');
    }

    public function updateLevel(Request $request, ClassLevel $level)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:80'],
            'order_index' => ['nullable','integer'],
            'section'     => ['required','in:creche,nursery,primary,junior_secondary,senior_secondary'],
        ]);
        $level->update($data);
        return back()->with('success', 'Class level updated.');
    }

    public function destroyLevel(ClassLevel $level)
    {
        if ($level->classArms()->count()) {
            return back()->withErrors(['error' => 'Cannot delete a level that has class arms.']);
        }
        $level->delete();
        return back()->with('success', 'Class level deleted.');
    }

    // ── Class Arms ────────────────────────────────────────────────────
    public function arms()
    {
        $classLevels = ClassLevel::with('classArms')->orderBy('order_index')->get();
        $arms        = ClassArm::with(['classLevel', 'students', 'formTutor'])
                        ->orderBy('class_level_id')->get();
        $teachers    = User::activeStaff($this->tenantId())
            ->teachers()
            ->get();
        $levels = $classLevels;
        return view('classes.arms', compact('classLevels', 'levels', 'arms', 'teachers'));
    }

    public function storeArm(Request $request)
    {
        $data = $request->validate([
            'class_level_id'  => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'name'            => ['required','string','max:20'],
            'form_tutor_id'   => ['nullable', $this->tenantUserRule()],
            'form_teacher_id' => ['nullable', $this->tenantUserRule()],
            'capacity'        => ['nullable','integer','min:1'],
        ]);
        if (empty($data['form_tutor_id']) && !empty($data['form_teacher_id'])) {
            $data['form_tutor_id'] = $data['form_teacher_id'];
        }
        unset($data['form_teacher_id']);
        ClassArm::create($data);
        return back()->with('success', 'Class arm created.');
    }

    public function updateArm(Request $request, ClassArm $arm)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:20'],
            'form_tutor_id' => ['nullable', $this->tenantUserRule()],
            'capacity'      => ['nullable', 'integer', 'min:1'],
        ]);
        $arm->update($data);
        return back()->with('success', 'Class arm updated.');
    }

    public function destroyArm(ClassArm $arm)
    {
        if ($arm->enrollments()->count()) {
            return back()->withErrors(['error' => 'Cannot delete a class with enrolled students.']);
        }
        $arm->delete();
        return back()->with('success', 'Class arm deleted.');
    }

    // ── Class Detail ──────────────────────────────────────────────────
    public function show(ClassArm $classArm)
    {
        $classArm->load(['classLevel', 'subjects']);
        $classArm->setRelation('students', \App\Models\Student::where('current_class_arm_id', $classArm->id)
            ->where('status', \App\Models\Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get());
        $arm = $classArm;
        $staffMap = User::where('tenant_id', $this->tenantId())->pluck('name', 'id')->toArray();
        $teachers = User::activeStaff($this->tenantId())
            ->teachers()
            ->get();
        $allSubjects = Subject::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $currentSession = \App\Models\AcademicSession::where('tenant_id', $this->tenantId())
            ->where('is_current', true)
            ->first();

        return view('classes.show', compact('classArm', 'arm', 'staffMap', 'teachers', 'allSubjects', 'currentSession'));
    }

    // ── Subject Assignments ───────────────────────────────────────────
    public function subjects(ClassArm $classArm)
    {
        $classArm->load(['classLevel', 'subjects']);
        $allSubjects = Subject::where('is_active', true)->get();
        return view('classes.subjects', compact('classArm', 'allSubjects'));
    }

    public function assignSubject(Request $request)
    {
        $data = $request->validate([
            'subject_id'   => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
            'teacher_id'   => ['nullable', $this->tenantUserRule()],
            'session_id'   => ['nullable', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'target'       => ['nullable', 'string'],
            'class_arm_id' => ['nullable', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $target = $data['target']
            ?? (!empty($data['class_arm_id']) ? 'arm:' . $data['class_arm_id'] : null);

        if (!$target) {
            return back()->withErrors(['target' => 'Choose a class or a class level to assign to.']);
        }

        [$type, $id] = array_pad(explode(':', $target, 2), 2, null);

        if ($type === 'level') {
            $level = ClassLevel::where('tenant_id', $this->tenantId())->find($id);
            if (!$level) {
                return back()->withErrors(['target' => 'Selected class level not found.']);
            }
            $arms = ClassArm::where('tenant_id', $this->tenantId())
                ->where('class_level_id', $level->id)
                ->get();
            if ($arms->isEmpty()) {
                return back()->withErrors(['target' => "No class arms exist under {$level->name} yet — add an arm first."]);
            }
            $scopeLabel = "all {$level->name} arms";
        } else {
            $arm = ClassArm::with('classLevel')->where('tenant_id', $this->tenantId())->find($id);
            if (!$arm) {
                return back()->withErrors(['target' => 'Selected class not found.']);
            }
            $arms = collect([$arm]);
            $scopeLabel = trim(optional($arm->classLevel)->name . ' ' . $arm->name);
        }

        $assigned = 0;
        $updated  = 0;
        foreach ($arms as $arm) {
            $sessionId = $data['session_id'] ?? \App\Models\AcademicSession::where('tenant_id', $arm->tenant_id)->where('is_current', true)->value('id');

            $existingPivot = \Illuminate\Support\Facades\DB::table('class_arm_subjects')
                ->where('class_arm_id', $arm->id)
                ->where('subject_id', $data['subject_id'])
                ->where('session_id', $sessionId)
                ->first();

            if ($existingPivot) {
                \Illuminate\Support\Facades\DB::table('class_arm_subjects')
                    ->where('id', $existingPivot->id)
                    ->update(['teacher_id' => $data['teacher_id'] ?? null, 'updated_at' => now()]);
                $updated++;
            } else {
                $arm->subjects()->attach($data['subject_id'], [
                    'tenant_id'  => $arm->tenant_id,
                    'teacher_id' => $data['teacher_id'] ?? null,
                    'session_id' => $sessionId,
                    'is_active'  => true,
                ]);
                $assigned++;
            }
        }

        if ($assigned === 0 && $updated === 0) {
            return back()->with('success', "Subject was already assigned to {$scopeLabel}.");
        }

        $parts = [];
        if ($assigned > 0) $parts[] = "assigned to {$assigned} " . \Illuminate\Support\Str::plural('class', $assigned);
        if ($updated > 0)  $parts[] = "teacher updated for {$updated} " . \Illuminate\Support\Str::plural('class', $updated);

        return back()->with('success', "Subject {$scopeLabel}: " . implode(', ', $parts) . '.');
    }

    public function storeSubject(Request $request, ClassArm $classArm)
    {
        $data = $request->validate([
            'subject_id'  => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
            'teacher_id'  => ['nullable', $this->tenantUserRule()],
        ]);

        $exists = $classArm->subjects()->where('subject_id', $data['subject_id'])->exists();
        if ($exists) {
            return back()->withErrors(['error' => 'Subject already assigned.']);
        }

        $classArm->subjects()->attach($data['subject_id'], [
            'tenant_id'  => $classArm->tenant_id,
            'teacher_id' => $data['teacher_id'],
            'is_active'  => true,
        ]);

        return back()->with('success', 'Subject added to class.');
    }

    public function toggleSubject(Request $request, $cas)
    {
        $pivot = \Illuminate\Support\Facades\DB::table('class_arm_subjects')
            ->where('tenant_id', $this->tenantId())
            ->where('id', $cas)
            ->first();
        if ($pivot) {
            \Illuminate\Support\Facades\DB::table('class_arm_subjects')
                ->where('tenant_id', $this->tenantId())
                ->where('id', $cas)
                ->update(['is_active' => !$pivot->is_active]);
        }
        return back()->with('success', 'Subject status toggled.');
    }

    // ── Grading Systems ───────────────────────────────────────────────
    public function grading()
    {
        $levels  = ClassLevel::with('gradingSystems')->orderBy('order_index')->get();
        $systems = GradingSystem::orderBy('class_level_id')->orderByDesc('min_score')->get();
        return view('classes.grading', compact('levels', 'systems'));
    }

    public function storeGrade(Request $request)
    {
        $request->merge(['class_level_ids' => $this->normaliseClassLevelIds($request)]);

        $data = $request->validate([
            'class_level_ids'   => ['required','array','min:1'],
            'class_level_ids.*' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'grade_letter'      => ['required','string','max:5'],
            'min_score'         => ['required','integer','min:0','max:100'],
            'max_score'         => ['required','integer','min:0','max:100'],
            'remark'            => ['required','string','max:100'],
            'grade_point'       => ['nullable','integer'],
            'is_pass_grade'     => ['boolean'],
        ]);

        if ((int) $data['min_score'] > (int) $data['max_score']) {
            return back()->withInput()->withErrors(['min_score' => 'Minimum score cannot be greater than maximum score.']);
        }

        $data['is_pass_grade'] = $request->boolean('is_pass_grade', true);
        $data['grade_point']   = $data['grade_point'] ?? 0;
        $conflicts = [];

        foreach ($data['class_level_ids'] as $levelId) {
            $overlap = GradingSystem::where('class_level_id', $levelId)
                ->where('grade_letter', '!=', $data['grade_letter'])
                ->where('min_score', '<=', $data['max_score'])
                ->where('max_score', '>=', $data['min_score'])
                ->first();
            if ($overlap) {
                $levelName = ClassLevel::find($levelId)?->name ?? "Class level {$levelId}";
                $conflicts[] = "{$levelName}: {$data['min_score']}–{$data['max_score']} overlaps {$overlap->grade_letter} ({$overlap->min_score}–{$overlap->max_score}).";
            }
        }

        if ($conflicts) {
            return back()->withInput()->withErrors(['min_score' => implode(' ', $conflicts)]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach ($data['class_level_ids'] as $levelId) {
                GradingSystem::updateOrCreate(
                    ['class_level_id' => $levelId, 'grade_letter' => $data['grade_letter']],
                    [
                        'min_score' => $data['min_score'],
                        'max_score' => $data['max_score'],
                        'remark' => $data['remark'],
                        'grade_point' => $data['grade_point'],
                        'is_pass_grade' => $data['is_pass_grade'],
                    ]
                );
            }
        });

        $count = count($data['class_level_ids']);
        return back()->with('success', "Grade entry saved for {$count} class " . \Illuminate\Support\Str::plural('level', $count) . '.');
    }

    public function destroyGrade(GradingSystem $grade)
    {
        $grade->delete();
        return back()->with('success', 'Grade entry removed.');
    }

    // ── Promotion Rules ───────────────────────────────────────────────
    public function promotion()
    {
        $levels = ClassLevel::with(['classArms', 'promotionRule'])->orderBy('order_index')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        return view('classes.promotion', compact('levels', 'subjects'));
    }

    public function savePromotion(Request $request)
    {
        $request->merge(['class_level_ids' => $this->normaliseClassLevelIds($request)]);

        $data = $request->validate([
            'class_level_ids'             => ['required','array','min:1'],
            'class_level_ids.*'           => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'min_required_average'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_failed_subjects_allowed' => ['nullable', 'integer', 'min:0'],
            'compulsory_subject_ids'      => ['nullable', 'array'],
            'compulsory_subject_ids.*'    => [Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $duplicateLevels = [];
        foreach ($data['class_level_ids'] as $levelId) {
            if (PromotionRule::where('class_level_id', $levelId)->count() > 1) {
                $duplicateLevels[] = ClassLevel::find($levelId)?->name ?? "Class level {$levelId}";
            }
        }
        if ($duplicateLevels) {
            return back()->withInput()->withErrors([
                'class_level_ids' => 'Conflicting promotion rules already exist for: ' . implode(', ', $duplicateLevels) . '. Resolve the duplicate records before applying a bulk rule.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach ($data['class_level_ids'] as $levelId) {
                PromotionRule::updateOrCreate(
                    ['class_level_id' => $levelId],
                    [
                        'min_required_average' => $data['min_required_average'] ?? null,
                        'max_failed_subjects_allowed' => $data['max_failed_subjects_allowed'] ?? null,
                        'compulsory_subject_ids' => $data['compulsory_subject_ids'] ?? [],
                    ]
                );
            }
        });

        $count = count($data['class_level_ids']);
        return back()->with('success', "Promotion rule saved for {$count} class " . \Illuminate\Support\Str::plural('level', $count) . '.');
    }

    // ── Manual Bulk Promotion ─────────────────────────────────────────
    public function bulkPromote(Request $request)
    {
        $operations = $request->input('operations');
        if (!is_array($operations) || empty($operations)) {
            $operations = [[
                'from_class_arm_id' => $request->input('from_class_arm_id'),
                'to_class_arm_id' => $request->input('to_class_arm_id'),
                'term_id' => $request->input('term_id'),
                'min_average' => $request->input('min_average', 45),
                'student_ids' => $request->input('student_ids', []),
            ]];
        }
        $request->merge(['operations' => $operations]);

        $data = $request->validate([
            'operations'                     => ['required','array','min:1','max:20'],
            'operations.*.from_class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'operations.*.to_class_arm_id'   => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'operations.*.term_id'           => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'operations.*.min_average'       => ['required','numeric','min:0','max:100'],
            'operations.*.student_ids'       => ['nullable','array'],
            'operations.*.student_ids.*'     => [Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', \App\Models\Student::STATUS_ACTIVE))],
        ]);

        $plans = [];
        foreach ($data['operations'] as $index => $operation) {
            $plan = $this->buildBulkPromotionPlan($operation);
            $plan['operation_index'] = $index;
            $plans[] = $plan;
        }

        $classArms = ClassArm::with('classLevel')->orderBy('class_level_id')->get();
        $terms = \App\Models\Term::with('session')->latest()->get();

        if ($request->boolean('preview_only')) {
            $preview = ['operations' => $plans];
            $submittedOperations = $data['operations'];
            return view('classes.bulk-promote', compact('classArms', 'terms', 'preview', 'submittedOperations'));
        }

        $promoted = 0;
        $blocked = 0;
        $operationErrors = [];
        $operationResults = [];

        foreach ($plans as $plan) {
            if (!empty($plan['error'])) {
                $operationErrors[] = $plan['error'];
                continue;
            }

            $blocked += $plan['blocked_count'];
            $movedThisOperation = 0;

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($plan, &$movedThisOperation) {
                    foreach ($plan['eligible'] as $item) {
                        $student = $item['student'];

                        $lockedStudent = \App\Models\Student::whereKey($student->id)->lockForUpdate()->first();
                        if (!$lockedStudent || (int) $lockedStudent->current_class_arm_id !== (int) $plan['fromArm']->id) {
                            continue;
                        }

                        StudentEnrollment::where('student_id', $student->id)
                            ->where('is_current', true)
                            ->update([
                                'is_current' => false,
                                'end_date' => now()->toDateString(),
                                'status' => StudentEnrollment::STATUS_CLOSED,
                                'ended_by' => auth()->id(),
                                'ended_reason' => 'manual_promotion',
                            ]);

                        StudentEnrollment::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'class_arm_id' => $plan['toArm']->id,
                                'session_id' => $plan['session_id'],
                                'term_id' => $plan['term']->id,
                            ],
                            [
                                'start_date' => now()->toDateString(),
                                'end_date' => null,
                                'is_current' => true,
                                'status' => StudentEnrollment::STATUS_ACTIVE,
                                'created_by' => auth()->id(),
                                'ended_by' => null,
                                'ended_reason' => null,
                            ]
                        );

                        $lockedStudent->update(['current_class_arm_id' => $plan['toArm']->id]);
                        \App\Models\TermlySummary::where('student_id', $student->id)
                            ->where('term_id', $plan['term']->id)
                            ->update(['promotion_status' => 'promoted']);

                        $movedThisOperation++;
                    }
                });

                $promoted += $movedThisOperation;
                $operationResults[] = trim($plan['fromArm']->classLevel->name . ' ' . $plan['fromArm']->name)
                    . ' → ' . trim($plan['toArm']->classLevel->name . ' ' . $plan['toArm']->name)
                    . ": {$movedThisOperation} promoted, {$plan['blocked_count']} blocked";
            } catch (\Throwable $e) {
                report($e);
                $operationErrors[] = trim($plan['fromArm']->classLevel->name . ' ' . $plan['fromArm']->name)
                    . ': promotion was rolled back safely because an unexpected database error occurred.';
            }
        }

        $response = back()->with('success', "Manual promotion complete: {$promoted} promoted; {$blocked} blocked by eligibility checks.")
            ->with('promotion_results', $operationResults);

        if ($operationErrors) {
            $response->with('promotion_errors', $operationErrors);
        }

        return $response;
    }

    public function bulkPromotePage()
    {
        $classArms = ClassArm::with('classLevel')->orderBy('class_level_id')->get();
        $terms     = \App\Models\Term::with('session')->latest()->get();
        return view('classes.bulk-promote', compact('classArms', 'terms'));
    }

    // ── Promotion Preview (dry run) ───────────────────────────────────
    public function promotionPreview(\Illuminate\Http\Request $request)
    {
        $classArms = ClassArm::with('classLevel')->orderBy('class_level_id')->get();
        $terms     = \App\Models\Term::with('session')->latest()->get();

        if (!$request->filled('class_arm_id') || !$request->filled('term_id')) {
            return view('classes.promotion-engine', compact('classArms', 'terms'));
        }

        $arm  = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term = \App\Models\Term::findOrFail($request->term_id);
        $rule = PromotionRule::where('class_level_id', $arm->class_level_id)->first();

        $students = \App\Models\Student::where('current_class_arm_id', $arm->id)
            ->where('status', \App\Models\Student::STATUS_ACTIVE)->orderBy('first_name')->get();

        $results = [];
        foreach ($students as $student) {
            $summary = \App\Models\TermlySummary::where('student_id', $student->id)
                ->where('term_id', $term->id)->first();

            $canPromote = true;
            $reasons    = [];

            if (!$summary) {
                $canPromote = false;
                $reasons[]  = 'No report card computed';
            } elseif ($rule) {
                if ($rule->min_required_average && $summary->final_average < $rule->min_required_average) {
                    $canPromote = false;
                    $reasons[]  = "Average {$summary->final_average}% < required {$rule->min_required_average}%";
                }
                if ($rule->max_failed_subjects_allowed !== null && ($summary->subjects_failed ?? 0) > $rule->max_failed_subjects_allowed) {
                    $canPromote = false;
                    $reasons[]  = "Failed {$summary->subjects_failed} subjects (max {$rule->max_failed_subjects_allowed} allowed)";
                }
                if (!empty($rule->compulsory_subject_ids) && $summary->subject_breakdown) {
                    foreach ($summary->subject_breakdown as $sub) {
                        if (in_array($sub['subject_id'] ?? 0, $rule->compulsory_subject_ids) && !($sub['is_pass'] ?? true)) {
                            $canPromote = false;
                            $reasons[]  = "Failed compulsory subject: " . ($sub['subject'] ?? 'Unknown');
                        }
                    }
                }
            }

            $results[] = [
                'student'     => $student,
                'summary'     => $summary,
                'can_promote' => $canPromote,
                'reasons'     => $reasons,
            ];
        }

        $promoteCount = collect($results)->where('can_promote', true)->count();
        $repeatCount  = collect($results)->where('can_promote', false)->count();

        $nextLevel = ClassLevel::where('tenant_id', $this->tenantId())
            ->where('order_index', '>', $arm->classLevel->order_index)
            ->orderBy('order_index')
            ->first();
        $nextArms = $nextLevel
            ? ClassArm::with('classLevel')->where('class_level_id', $nextLevel->id)->get()
            : collect();
        $isTerminalLevel = !$nextLevel;

        return view('classes.promotion-engine', compact(
            'classArms', 'terms', 'arm', 'term', 'rule',
            'results', 'promoteCount', 'repeatCount', 'nextArms', 'isTerminalLevel'
        ));
    }

    // ── Run Promotion ─────────────────────────────────────────────────
    public function runPromotion(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'class_arm_id'      => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'           => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'promote_to_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'repeat_arm_id'     => ['nullable', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'student_ids'       => ['array'],
            'student_ids.*'     => [Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', \App\Models\Student::STATUS_ACTIVE))],
            'repeat_ids'        => ['array'],
            'repeat_ids.*'      => [Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', \App\Models\Student::STATUS_ACTIVE))],
        ]);

        $fromArm     = ClassArm::with('classLevel')->findOrFail($data['class_arm_id']);
        $promoteArm  = ClassArm::with('classLevel')->findOrFail($data['promote_to_arm_id']);
        $term        = \App\Models\Term::findOrFail($data['term_id']);

        $nextLevel = ClassLevel::where('tenant_id', $this->tenantId())
            ->where('order_index', '>', $fromArm->classLevel->order_index)
            ->orderBy('order_index')
            ->first();
        if (!$nextLevel) {
            return back()->withInput()->withErrors(['promote_to_arm_id' => 'This is a terminal class level. Use the graduation workflow instead of promoting to another class.']);
        }
        if ((int) $promoteArm->class_level_id !== (int) $nextLevel->id) {
            return back()->withInput()->withErrors(['promote_to_arm_id' => "Invalid destination. Students from {$fromArm->classLevel->name} can only be promoted to {$nextLevel->name}."]);
        }

        $promoted = $repeated = 0;
        $nextSession = \App\Models\AcademicSession::where('tenant_id', $this->tenantId())
            ->where('is_current', true)->first();
        $sessionId = $nextSession?->id ?: $term->session_id;
        if (!$sessionId) {
            return back()->withErrors(['term_id' => 'No valid academic session is available for this promotion.']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $data, $fromArm, $promoteArm, $term, $sessionId, &$promoted, &$repeated
        ) {
            $moveStudent = function (int $sid, ClassArm $toArm, string $promotionStatus) use ($fromArm, $term, $sessionId) {
                $student = \App\Models\Student::whereKey($sid)->lockForUpdate()->first();
                if (!$student || (int) $student->current_class_arm_id !== (int) $fromArm->id) {
                    return false;
                }

                StudentEnrollment::where('student_id', $sid)
                    ->where('is_current', true)
                    ->update([
                        'is_current' => false,
                        'end_date' => now()->toDateString(),
                        'status' => StudentEnrollment::STATUS_CLOSED,
                        'ended_by' => auth()->id(),
                        'ended_reason' => $promotionStatus,
                    ]);

                StudentEnrollment::updateOrCreate(
                    [
                        'student_id' => $sid,
                        'class_arm_id' => $toArm->id,
                        'session_id' => $sessionId,
                        'term_id' => $term->id,
                    ],
                    [
                        'start_date' => now()->toDateString(),
                        'end_date' => null,
                        'is_current' => true,
                        'status' => StudentEnrollment::STATUS_ACTIVE,
                        'created_by' => auth()->id(),
                        'ended_by' => null,
                        'ended_reason' => null,
                    ]
                );

                $student->update(['current_class_arm_id' => $toArm->id]);
                \App\Models\TermlySummary::where('student_id', $sid)->where('term_id', $term->id)
                    ->update(['promotion_status' => $promotionStatus]);

                return true;
            };

            foreach ($data['student_ids'] ?? [] as $sid) {
                if ($moveStudent((int) $sid, $promoteArm, 'promoted')) {
                    $promoted++;
                }
            }

            if (!empty($data['repeat_ids']) && !empty($data['repeat_arm_id'])) {
                $repeatArm = ClassArm::findOrFail($data['repeat_arm_id']);
                if ((int) $repeatArm->class_level_id !== (int) $fromArm->class_level_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'repeat_arm_id' => 'Repeat students must remain within the same class level.',
                    ]);
                }
                foreach ($data['repeat_ids'] as $sid) {
                    if ($moveStudent((int) $sid, $repeatArm, 'repeat')) {
                        $repeated++;
                    }
                }
            }
        });

        return redirect()->route('classes.promotion.preview', [
            'class_arm_id' => $data['class_arm_id'],
            'term_id'      => $data['term_id'],
        ])->with('success', "✓ Promotion complete: {$promoted} promoted, {$repeated} set to repeat.");
    }

    // ── Promotion History ─────────────────────────────────────────────
    public function promotionHistory(\Illuminate\Http\Request $request)
    {
        $terms = \App\Models\Term::with('session')->latest()->get();
        $termId = $request->get('term_id', optional($terms->first())->id);

        $history = \App\Models\TermlySummary::where('term_id', $termId)
            ->whereIn('promotion_status', ['promoted', 'repeat'])
            ->with(['student', 'classArm.classLevel', 'term.session'])
            ->orderBy('promotion_status')
            ->paginate(30);

        return view('classes.promotion-history', compact('terms', 'termId', 'history'));
    }
}
