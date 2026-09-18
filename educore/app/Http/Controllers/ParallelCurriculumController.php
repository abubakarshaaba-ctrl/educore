<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ParallelCurriculumController extends Controller
{
    public function __construct(private readonly ParallelCurriculumService $service) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->canAccessExactModule('scores');
    }

    private function assertEnabled(): void
    {
        abort_unless(
            $this->service->enabledForTenant($this->tenantId()),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    private function assertManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only administrators can configure parallel curricula.');
    }

    private function classForTenant(int $id): ParallelCurriculumClass
    {
        return ParallelCurriculumClass::with(['curriculum', 'assessmentTemplate', 'curriculum.defaultAssessmentTemplate'])
            ->findOrFail($id);
    }

    private function assertCanEnter(ParallelCurriculumClass $class, int $subjectId): void
    {
        if ($this->canManage()) {
            return;
        }

        $allowed = ParallelCurriculumClassSubject::where('parallel_curriculum_class_id', $class->id)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', auth()->id())
            ->where('is_active', true)
            ->exists();

        abort_unless($allowed, 403, 'You are not assigned to this parallel-curriculum class and subject.');
    }

    public function index()
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $canManage = $this->canManage();
        $currentSession = AcademicSession::current()->first();
        $currentTerm = Term::current()->with('session')->first();

        $curricula = ParallelCurriculum::with([
            'defaultAssessmentTemplate',
            'classes.assessmentTemplate',
            'classes.subjectAssignments.subject',
            'classes.subjectAssignments.teacher',
        ])->orderBy('name')->get();

        $workspaces = $curricula->flatMap(function (ParallelCurriculum $curriculum) use ($canManage) {
            return $curriculum->classes
                ->where('is_active', true)
                ->flatMap(function (ParallelCurriculumClass $class) use ($curriculum, $canManage) {
                    return $class->subjectAssignments
                        ->where('is_active', true)
                        ->filter(fn ($assignment) => $canManage || (int) $assignment->teacher_id === (int) auth()->id())
                        ->map(fn ($assignment) => [
                            'curriculum' => $curriculum,
                            'class' => $class,
                            'assignment' => $assignment,
                        ]);
                });
        })->values();

        $templates = $canManage
            ? AssessmentTemplate::with('components')->where('status', AssessmentTemplate::STATUS_ACTIVE)->orderBy('name')->get()
            : collect();

        $subjects = $canManage ? Subject::where('is_active', true)->orderBy('name')->get() : collect();
        $classLevels = $canManage ? ClassLevel::orderBy('order_index')->orderBy('name')->get() : collect();
        $students = $canManage ? Student::active()->orderBy('last_name')->orderBy('first_name')->get() : collect();
        $staff = $canManage
            ? User::where('tenant_id', $tenantId)->where('is_active', true)->whereIn('role', User::staffRoleNames())->orderBy('name')->get()
            : collect();

        $integrations = $canManage
            ? ParallelCurriculumIntegration::with(['curriculum', 'destinationClassLevel', 'destinationSubject'])
                ->orderBy('parallel_curriculum_id')->orderBy('destination_class_level_id')->get()
            : collect();

        $enrolments = ($canManage && $currentSession)
            ? ParallelCurriculumEnrolment::with(['student.currentClassArm.classLevel', 'curriculumClass', 'curriculum'])
                ->where('session_id', $currentSession->id)
                ->where('is_active', true)
                ->orderBy('parallel_curriculum_class_id')
                ->get()
            : collect();

        $recentComposites = $currentTerm
            ? ParallelCurriculumComposite::with(['student', 'curriculum', 'curriculumClass', 'destinationSubject'])
                ->where('term_id', $currentTerm->id)
                ->latest('computed_at')
                ->limit(30)
                ->get()
            : collect();

        return view('parallel-curriculum.index', compact(
            'canManage', 'currentSession', 'currentTerm', 'curricula', 'workspaces',
            'templates', 'subjects', 'classLevels', 'students', 'staff',
            'integrations', 'enrolments', 'recentComposites'
        ));
    }

    public function storeCurriculum(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'default_assessment_template_id' => [
                'required',
                Rule::exists('assessment_templates', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        ParallelCurriculum::create([
            'tenant_id' => $tenantId,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'default_assessment_template_id' => $data['default_assessment_template_id'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Parallel curriculum created.');
    }

    public function storeClass(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'assessment_template_id' => ['nullable', Rule::exists('assessment_templates', 'id')->where('tenant_id', $tenantId)],
        ]);

        ParallelCurriculumClass::create([
            'tenant_id' => $tenantId,
            'parallel_curriculum_id' => $data['parallel_curriculum_id'],
            'assessment_template_id' => $data['assessment_template_id'] ?? null,
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Parallel curriculum class created.');
    }

    public function storeClassSubject(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        ParallelCurriculumClassSubject::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'parallel_curriculum_class_id' => $data['parallel_curriculum_class_id'],
                'subject_id' => $data['subject_id'],
            ],
            [
                'teacher_id' => $data['teacher_id'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Subject assigned to parallel curriculum class.');
    }

    public function destroyClassSubject(ParallelCurriculumClassSubject $assignment)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $assignment->tenant_id === $this->tenantId(), 403);

        $hasScores = ParallelCurriculumScore::where('parallel_curriculum_class_id', $assignment->parallel_curriculum_class_id)
            ->where('subject_id', $assignment->subject_id)
            ->exists();

        if ($hasScores) {
            $assignment->update(['is_active' => false]);

            return back()->with('success', 'Subject archived because result records already exist.');
        }

        $assignment->delete();

        return back()->with('success', 'Subject removed from parallel curriculum class.');
    }

    public function assignStudents(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId)],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['parallel_curriculum_class_id']);

        foreach (collect($data['student_ids'])->map(fn ($id) => (int) $id)->unique() as $studentId) {
            ParallelCurriculumEnrolment::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_id' => $class->parallel_curriculum_id,
                    'student_id' => $studentId,
                    'session_id' => $data['session_id'],
                ],
                [
                    'parallel_curriculum_class_id' => $class->id,
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Student parallel-class assignments updated.');
    }

    public function destroyEnrolment(ParallelCurriculumEnrolment $enrolment)
    {
        $this->assertEnabled();
        $this->assertManage();
        abort_unless((int) $enrolment->tenant_id === $this->tenantId(), 403);

        $hasScores = ParallelCurriculumScore::where('parallel_curriculum_id', $enrolment->parallel_curriculum_id)
            ->where('student_id', $enrolment->student_id)
            ->where('session_id', $enrolment->session_id)
            ->exists();

        if ($hasScores) {
            $enrolment->update(['is_active' => false]);

            return back()->with('success', 'Student assignment archived because result records already exist.');
        }

        $enrolment->delete();

        return back()->with('success', 'Student removed from the parallel curriculum class.');
    }

    public function storeIntegration(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'destination_class_level_ids' => ['required', 'array', 'min:1'],
            'destination_class_level_ids.*' => ['integer', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)],
            'destination_subject_id' => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'minimum_completed_subjects' => ['nullable', 'integer', 'min:1', 'max:50'],
            'require_all_subjects' => ['nullable', 'boolean'],
            'auto_sync' => ['nullable', 'boolean'],
        ]);

        foreach (collect($data['destination_class_level_ids'])->map(fn ($id) => (int) $id)->unique() as $classLevelId) {
            ParallelCurriculumIntegration::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'parallel_curriculum_id' => $data['parallel_curriculum_id'],
                    'destination_class_level_id' => $classLevelId,
                ],
                [
                    'destination_subject_id' => $data['destination_subject_id'],
                    'calculation_method' => 'arithmetic_mean',
                    'require_all_subjects' => $request->boolean('require_all_subjects', true),
                    'minimum_completed_subjects' => (int) ($data['minimum_completed_subjects'] ?? 1),
                    'auto_sync' => $request->boolean('auto_sync', true),
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Conventional result integration rule saved.');
    }

    public function scoreSheet(Request $request)
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        $this->assertCanEnter($class, (int) $data['subject_id']);

        $term = Term::with('session')->findOrFail($data['term_id']);
        $subject = Subject::findOrFail($data['subject_id']);

        abort_unless(
            ParallelCurriculumClassSubject::where('parallel_curriculum_class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->exists(),
            422,
            'This subject is not active in the selected parallel curriculum class.'
        );

        $components = $this->service->componentsForClass($class);
        $template = $this->service->templateForClass($class);

        $enrolments = ParallelCurriculumEnrolment::with('student')
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($enrolment) => strtolower($enrolment->student?->last_name.' '.$enrolment->student?->first_name))
            ->values();

        $scores = ParallelCurriculumScore::where('parallel_curriculum_class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $enrolments->pluck('student_id'))
            ->get()
            ->keyBy(fn ($score) => $score->student_id.':'.$score->assessment_template_component_id);

        return view('parallel-curriculum.score-sheet', compact(
            'class', 'subject', 'term', 'template', 'components', 'enrolments', 'scores'
        ));
    }

    public function saveScores(Request $request)
    {
        $this->assertEnabled();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'scores' => ['required', 'array'],
        ]);

        $class = $this->classForTenant((int) $data['class_id']);
        $this->assertCanEnter($class, (int) $data['subject_id']);
        $term = Term::findOrFail($data['term_id']);
        $components = $this->service->componentsForClass($class)->keyBy('id');

        abort_if($components->isEmpty(), 422, 'Assign an assessment template to this parallel curriculum class first.');

        $enrolments = ParallelCurriculumEnrolment::where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->get()
            ->keyBy('student_id');

        $errors = [];
        foreach ($data['scores'] as $studentId => $componentValues) {
            if (! $enrolments->has((int) $studentId) || ! is_array($componentValues)) {
                $errors["scores.{$studentId}"][] = 'Student is not active in this parallel curriculum class.';
                continue;
            }

            foreach ($componentValues as $componentId => $value) {
                $component = $components->get((int) $componentId);
                if (! $component) {
                    $errors["scores.{$studentId}.{$componentId}"][] = 'Invalid assessment component.';
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                if (! is_numeric($value) || (float) $value < 0 || (float) $value > (float) $component->weight_percentage) {
                    $errors["scores.{$studentId}.{$componentId}"][] =
                        "Enter a score from 0 to {$component->weight_percentage}.";
                }
            }
        }

        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($data, $class, $term, $components, $enrolments, $tenantId): void {
            foreach ($data['scores'] as $studentId => $componentValues) {
                $enrolment = $enrolments->get((int) $studentId);
                foreach ($componentValues as $componentId => $value) {
                    $component = $components->get((int) $componentId);

                    $key = [
                        'tenant_id' => $tenantId,
                        'parallel_curriculum_id' => $class->parallel_curriculum_id,
                        'student_id' => (int) $studentId,
                        'subject_id' => (int) $data['subject_id'],
                        'assessment_template_component_id' => $component->id,
                        'term_id' => $term->id,
                    ];

                    if ($value === null || $value === '') {
                        ParallelCurriculumScore::where($key)->delete();
                        continue;
                    }

                    ParallelCurriculumScore::updateOrCreate($key, [
                        'parallel_curriculum_class_id' => $class->id,
                        'session_id' => $term->session_id,
                        'entered_by' => auth()->id(),
                        'score' => round(min((float) $value, (float) $component->weight_percentage), 2),
                        'entered_at' => now(),
                    ]);
                }
            }
        });

        foreach ($enrolments as $enrolment) {
            $this->service->syncStudent($enrolment, $term, false);
        }

        return back()->with('success', 'Parallel curriculum scores saved and eligible composite results synchronized.');
    }

    public function sync(Request $request)
    {
        $this->assertEnabled();
        $this->assertManage();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'parallel_curriculum_id' => ['required', Rule::exists('parallel_curricula', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $curriculum = ParallelCurriculum::findOrFail($data['parallel_curriculum_id']);
        $term = Term::findOrFail($data['term_id']);
        $result = $this->service->syncCurriculum($curriculum, $term, true);

        return back()->with(
            'success',
            "Parallel results synchronized: {$result['synced']} synced, {$result['pending']} pending, ".
            "{$result['conflict']} conflict, {$result['locked']} locked, {$result['unmapped']} unmapped."
        );
    }

    public function breakdown(ParallelCurriculumComposite $composite)
    {
        $this->assertEnabled();
        abort_unless((int) $composite->tenant_id === $this->tenantId(), 403);

        $composite->load([
            'student',
            'curriculum',
            'curriculumClass',
            'conventionalClassArm.classLevel',
            'destinationSubject',
            'term.session',
            'integration',
        ]);

        $distribution = collect();
        if ($composite->destination_subject_id) {
            $distribution = \App\Models\Score::where('student_id', $composite->student_id)
                ->where('subject_id', $composite->destination_subject_id)
                ->where('term_id', $composite->term_id)
                ->where('score_source', ParallelCurriculumService::SCORE_SOURCE)
                ->where('source_reference_id', $composite->id)
                ->with('assessmentType')
                ->orderBy('assessment_type_id')
                ->get();
        }

        return view('parallel-curriculum.breakdown', compact('composite', 'distribution'));
    }
}
