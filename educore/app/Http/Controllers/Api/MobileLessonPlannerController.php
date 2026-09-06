<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\CurriculumTopic;
use App\Models\LessonNoteValidation;
use App\Models\LessonPlan;
use App\Models\LessonPlanSource;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Curriculum\CurriculumRetrievalService;
use App\Services\LessonAiService;
use App\Services\LessonPlanning\GroundedLessonNoteService;
use App\Services\LessonPlanning\StructuredLessonPlanService;
use App\Services\Mobile\MobileIdempotencyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobileLessonPlannerController extends Controller
{
    public function __construct(private readonly MobileIdempotencyService $idempotency) {}

    public function options(Request $request)
    {
        $user = $this->guardPlanner($request);
        $session = AcademicSession::current()->first();
        $assignments = ClassArmSubject::with(['classArm.classLevel', 'subject'])
            ->when($session, fn (Builder $query) => $query->where('session_id', $session->id))
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('teacher_id', $user->id))
            ->get()->filter(fn (ClassArmSubject $item) => $item->classArm?->classLevel && $item->subject);

        if ($user->isAdmin() && $assignments->isEmpty()) {
            $classes = ClassArm::with('classLevel')->orderBy('class_level_id')->orderBy('name')->get();
            $subjects = Subject::active()->orderBy('name')->get();
            $assignmentPayload = $classes->map(fn (ClassArm $class) => [
                'class_arm_id' => $class->id,
                'class_level_id' => $class->class_level_id,
                'class_name' => $this->className($class),
                'subjects' => $subjects->map(fn (Subject $subject) => ['id' => $subject->id, 'name' => $subject->name])->values(),
            ]);
        } else {
            $assignmentPayload = $assignments->groupBy('class_arm_id')->map(function ($items) {
                $class = $items->first()->classArm;

                return [
                    'class_arm_id' => $class->id,
                    'class_level_id' => $class->class_level_id,
                    'class_name' => $this->className($class),
                    'subjects' => $items->pluck('subject')->unique('id')->sortBy('name')->map(fn (Subject $subject) => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ])->values(),
                ];
            })->values();
        }

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'session' => $session?->only(['id', 'name']),
            'terms' => Term::with('session')->orderByDesc('id')->take(9)->get()->map(fn (Term $term) => [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
                'is_current' => $term->is_current,
            ]),
            'assignments' => $assignmentPayload,
            'curriculum_types' => [
                ['key' => 'nerdc', 'label' => 'Nigerian / NERDC'],
                ['key' => 'british', 'label' => 'British'],
            ],
            'delivery_types' => collect([
                'regular' => 'Regular lesson',
                'carry_forward' => 'Carry-forward prerequisite',
                'remedial' => 'Remedial lesson',
                'revision' => 'Revision',
                'enrichment' => 'Enrichment',
            ])->map(fn (string $label, string $key) => compact('key', 'label'))->values(),
            'topics' => CurriculumTopic::where('status', 'active')->orderBy('topic')->get()->map(fn (CurriculumTopic $topic) => [
                'id' => $topic->id,
                'subject_id' => $topic->subject_id,
                'class_level_id' => $topic->curriculum_level_id,
                'term_id' => $topic->term_id,
                'topic' => $topic->topic,
                'subtopics' => $topic->subtopics,
            ]),
        ]);
    }

    public function index(Request $request)
    {
        $user = $this->guardPlanner($request);
        $data = $request->validate([
            'subject_id' => ['nullable', 'integer'],
            'curriculum_type' => ['nullable', Rule::in(['nerdc', 'british'])],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'query' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $paginator = LessonPlan::with(['subject', 'classLevel', 'classArm', 'term'])
            ->where('teacher_id', $user->id)
            ->when($data['subject_id'] ?? null, fn (Builder $query, int $id) => $query->where('subject_id', $id))
            ->when($data['curriculum_type'] ?? null, fn (Builder $query, string $type) => $query->where('curriculum_type', $type))
            ->when($data['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($data['query'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested
                ->where('topic', 'like', "%{$search}%")->orWhere('subtopic', 'like', "%{$search}%")))
            ->latest()->paginate($data['per_page'] ?? 20);

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'lesson_plans' => collect($paginator->items())->map(fn (LessonPlan $plan) => $this->planPayload($plan, false)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guardPlanner($request);
        $data = $this->validatePlan($request, false);
        $requestId = $data['request_id'];
        unset($data['request_id'], $data['version']);
        $this->assertAssignment($user, $data);

        $response = $this->idempotency->execute($user, 'lesson-plan.create', $requestId, $data, function () use ($data, $user) {
            $attributes = $this->normalisePlanData($data);
            $attributes['teacher_id'] = $user->id;
            $plan = DB::transaction(function () use ($attributes) {
                $plan = LessonPlan::create($attributes);
                $this->recordRepositorySources($plan);

                return $plan;
            });

            return ['lesson_plan' => $this->planPayload($plan->fresh(), true)];
        });

        return response()->json(['contract_version' => 1] + $response, 201);
    }

    public function show(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'lesson_plan' => $this->planPayload($plan, true),
        ]);
    }

    public function update(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);
        $data = $this->validatePlan($request, true);
        $requestId = $data['request_id'];
        $version = $data['version'];
        unset($data['request_id'], $data['version']);
        $merged = array_merge($plan->only(['subject_id', 'class_level_id', 'class_arm_id']), $data);
        $this->assertAssignment($user, $merged);

        $response = $this->idempotency->execute($user, "lesson-plan.{$plan->id}.update", $requestId, ['version' => $version] + $data, function () use ($plan, $data, $version) {
            $this->assertVersion($plan, $version);
            DB::transaction(function () use ($plan, $data) {
                $plan->update($this->normalisePlanData($data, $plan));
                if (array_key_exists('structured_plan', $data)) {
                    $plan->repositorySources()->delete();
                    $this->recordRepositorySources($plan->fresh());
                }
            });

            return ['lesson_plan' => $this->planPayload($plan->fresh(), true)];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function generate(
        Request $request,
        int $lessonPlanId,
        StructuredLessonPlanService $structuredService,
        CurriculumRetrievalService $retrieval,
    ) {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'version' => ['required', 'string', 'max:80'],
        ]);

        $response = $this->idempotency->execute($user, "lesson-plan.{$plan->id}.generate", $data['request_id'], $data, function () use ($plan, $data, $structuredService, $retrieval) {
            $this->assertVersion($plan, $data['version']);
            $plan->loadMissing(['subject', 'classLevel', 'term']);
            $input = [
                'subject' => $plan->subject?->name,
                'class_level' => $plan->classLevel?->name,
                'topic' => $plan->topic,
                'subtopic' => $plan->subtopic,
                'curriculum_type' => $plan->curriculum_type,
                'section' => $plan->classLevel?->section ?: 'general',
                'term' => $plan->term?->name,
                'week' => $plan->week_number,
                'duration_minutes' => $plan->duration_minutes,
            ];

            if ($plan->curriculum_type === 'british') {
                $result = app(LessonAiService::class)->generateBritishPlan($input);
            } else {
                $context = $retrieval->compactContext($retrieval->forLessonPlan($plan));
                $structured = $structuredService->generate($input + [
                    'lesson' => $plan->lesson_number ?: '1',
                    'time' => $plan->lesson_time ?: '',
                    'average_age' => $plan->average_age ?: '',
                    'sex' => $plan->sex ?: 'Mixed',
                    'curriculum_origin' => $plan->curriculum_level_id,
                    'delivery_type' => $plan->delivery_type,
                    'repository_context' => $context,
                ]);
                $result = $structuredService->legacyFields($structured);
                $result['structured_plan']['repository_context'] = $context;
            }

            DB::transaction(function () use ($plan, $result) {
                $plan->update($this->normaliseGeneratedFields($result));
                $plan->repositorySources()->delete();
                $this->recordRepositorySources($plan->fresh());
            });

            return ['lesson_plan' => $this->planPayload($plan->fresh(), true)];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function generateNote(Request $request, int $lessonPlanId, GroundedLessonNoteService $service)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'version' => ['required', 'string', 'max:80'],
            'depth' => ['nullable', Rule::in(['concise', 'standard', 'detailed'])],
        ]);

        $response = $this->idempotency->execute($user, "lesson-plan.{$plan->id}.generate-note", $data['request_id'], $data, function () use ($plan, $data, $service, $user) {
            $this->assertVersion($plan, $data['version']);
            $plan->loadMissing(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
            $revision = $service->generate($plan, $user->id, $data['depth'] ?? 'standard');

            return [
                'revision' => $revision->revision,
                'lesson_plan' => $this->planPayload($plan->fresh(), true),
            ];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function updateNote(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'version' => ['required', 'string', 'max:80'],
            'note_text' => ['required', 'string', 'min:100'],
            'note_status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $response = $this->idempotency->execute($user, "lesson-plan.{$plan->id}.update-note", $data['request_id'], $data, function () use ($plan, $data) {
            $this->assertVersion($plan, $data['version']);
            $revision = $plan->noteRevisions()->latest('revision')->firstOrFail();
            $content = $revision->content;
            $content['sections'] = [[
                'heading' => $plan->topic,
                'subheading' => null,
                'content_blocks' => [['type' => 'paragraph', 'content' => $data['note_text']]],
            ]];
            DB::transaction(function () use ($plan, $revision, $content, $data) {
                $revision->update(['content' => $content, 'status' => $data['note_status'], 'teacher_edited' => true]);
                $plan->update(['lesson_notes' => '<h2>'.e($plan->topic).'</h2><p>'.nl2br(e($data['note_text'])).'</p>']);
            });

            return ['lesson_plan' => $this->planPayload($plan->fresh(), true)];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function publish(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'version' => ['required', 'string', 'max:80'],
        ]);

        $response = $this->idempotency->execute($user, "lesson-plan.{$plan->id}.publish", $data['request_id'], $data, function () use ($plan, $data) {
            $this->assertVersion($plan, $data['version']);
            $plan->update(['status' => 'published', 'published_at' => $plan->published_at ?: now()]);

            return ['lesson_plan' => $this->planPayload($plan->fresh(), true)];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function pdf(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId)->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);

        return Pdf::loadView('lesson-planner.print', ['lessonPlan' => $plan])
            ->setPaper('a4')->download(Str::slug($plan->topic).'-lesson-plan.pdf');
    }

    public function notePdf(Request $request, int $lessonPlanId)
    {
        $user = $this->guardPlanner($request);
        $plan = $this->ownedPlan($user->id, $lessonPlanId)->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        abort_unless(filled($plan->lesson_notes), 404, 'Generate or write the student note first.');

        return Pdf::loadView('lesson-planner.print-notes', ['lessonPlan' => $plan])
            ->setPaper('a4')->download(Str::slug($plan->topic).'-student-note.pdf');
    }

    private function validatePlan(Request $request, bool $partial): array
    {
        $presence = $partial ? 'sometimes' : 'required';
        $tenantId = $request->user()->tenant_id;
        $rules = [
            'request_id' => ['required', 'uuid'],
            'version' => [$partial ? 'required' : 'nullable', 'string', 'max:80'],
            'subject_id' => [$presence, 'integer', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'class_level_id' => [$presence, 'integer', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'class_arm_id' => ['nullable', 'integer', Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'term_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'curriculum_type' => [$presence, Rule::in(['nerdc', 'british'])],
            'curriculum_level_id' => ['nullable', 'integer', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'delivery_type' => [$presence, Rule::in(['regular', 'carry_forward', 'remedial', 'revision', 'enrichment'])],
            'topic' => [$presence, 'string', 'max:255'],
            'subtopic' => ['nullable', 'string', 'max:1000'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:52'],
            'lesson_number' => ['nullable', 'string', 'max:40'],
            'lesson_time' => ['nullable', 'string', 'max:40'],
            'average_age' => ['nullable', 'string', 'max:40'],
            'sex' => ['nullable', 'string', 'max:30'],
            'plan_date' => ['nullable', 'date'],
            'duration_minutes' => [$presence, 'integer', 'min:10', 'max:300'],
            'status' => [$presence, Rule::in(['draft', 'published'])],
            'previous_knowledge' => ['nullable', 'string'],
            'behavioural_objectives' => ['nullable', 'string'],
            'instructional_materials' => ['nullable', 'string'],
            'reference_materials' => ['nullable', 'string'],
            'set_induction' => ['nullable', 'string'],
            'presentation' => ['nullable', 'string'],
            'evaluation' => ['nullable', 'string'],
            'assignment' => ['nullable', 'string'],
            'learning_objectives' => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'starter_activity' => ['nullable', 'string'],
            'class_activity' => ['nullable', 'string'],
            'differentiation' => ['nullable', 'string'],
            'plenary' => ['nullable', 'string'],
            'assessment_for_learning' => ['nullable', 'string'],
            'structured_plan' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function normalisePlanData(array $data, ?LessonPlan $existing = null): array
    {
        if (($data['curriculum_type'] ?? $existing?->curriculum_type) === 'nerdc') {
            $data['class_activity'] = null;
            $data['conclusion'] = null;
        }
        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = $existing?->published_at ?: now();
        } elseif (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function normaliseGeneratedFields(array $result): array
    {
        $allowed = [
            'previous_knowledge', 'behavioural_objectives', 'instructional_materials', 'reference_materials',
            'set_induction', 'presentation', 'evaluation', 'assignment', 'learning_objectives', 'success_criteria',
            'starter_activity', 'class_activity', 'differentiation', 'plenary', 'assessment_for_learning', 'structured_plan',
        ];

        return collect($result)->only($allowed)->all() + ['ai_generated' => true];
    }

    private function assertAssignment($user, array $data): void
    {
        if ($user->isAdmin()) {
            return;
        }
        $sessionId = AcademicSession::current()->value('id');
        $query = ClassArmSubject::where('teacher_id', $user->id)->where('subject_id', $data['subject_id'])
            ->whereHas('classArm', fn (Builder $builder) => $builder->where('class_level_id', $data['class_level_id']))
            ->when($data['class_arm_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('class_arm_id', $id))
            ->when($sessionId, fn (Builder $builder, int $id) => $builder->where('session_id', $id));
        abort_unless($query->exists(), 403, 'You can only prepare plans for a subject and class assigned to you.');
    }

    private function assertVersion(LessonPlan $plan, string $version): void
    {
        $serverVersion = $this->version($plan);
        abort_unless(hash_equals($serverVersion, $version), 409, 'This lesson plan changed on the server. Reload it before saving or generating content.');
    }

    private function version(LessonPlan $plan): string
    {
        return $plan->updated_at?->toJSON() ?? '';
    }

    private function guardPlanner(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && ($user->isTeacher() || $user->isAdmin()), 403, 'Lesson Planner access is limited to authorized school teachers and administrators.');
        abort_unless($user->canAccessExactModule('lesson-planner'), 403, 'Lesson Planner is not enabled for this account.');

        return $user;
    }

    private function ownedPlan(int $userId, int $planId): LessonPlan
    {
        return LessonPlan::with(['subject', 'classLevel', 'classArm', 'term', 'teacher', 'currentNoteRevision'])
            ->where('teacher_id', $userId)->findOrFail($planId);
    }

    private function planPayload(LessonPlan $plan, bool $includeContent): array
    {
        $plan->loadMissing(['subject', 'classLevel', 'classArm', 'term']);
        $payload = [
            'id' => $plan->id,
            'version' => $this->version($plan),
            'subject' => $plan->subject ? ['id' => $plan->subject->id, 'name' => $plan->subject->name] : null,
            'class_level' => $plan->classLevel ? ['id' => $plan->classLevel->id, 'name' => $plan->classLevel->name] : null,
            'class_arm' => $plan->classArm ? ['id' => $plan->classArm->id, 'name' => $this->className($plan->classArm)] : null,
            'term' => $plan->term ? ['id' => $plan->term->id, 'name' => $plan->term->name] : null,
            'curriculum_type' => $plan->curriculum_type,
            'curriculum_level_id' => $plan->curriculum_level_id,
            'delivery_type' => $plan->delivery_type,
            'topic' => $plan->topic,
            'subtopic' => $plan->subtopic,
            'week_number' => $plan->week_number,
            'lesson_number' => $plan->lesson_number,
            'lesson_time' => $plan->lesson_time,
            'average_age' => $plan->average_age,
            'sex' => $plan->sex,
            'plan_date' => $plan->plan_date?->toDateString(),
            'duration_minutes' => $plan->duration_minutes,
            'status' => $plan->status,
            'ai_generated' => $plan->ai_generated,
            'has_note' => filled($plan->lesson_notes),
            'published_at' => $plan->published_at?->toIso8601String(),
            'updated_at' => $plan->updated_at?->toIso8601String(),
        ];
        if (! $includeContent) {
            return $payload;
        }

        $sections = collect($plan->sections())->map(fn (string $label, string $key) => [
            'key' => $key,
            'label' => $label,
            'content' => $plan->sectionValue($key),
        ])->values();
        $revision = $plan->noteRevisions()->latest('revision')->first();
        $validation = $revision ? LessonNoteValidation::where('lesson_note_revision_id', $revision->id)->latest('id')->first() : null;

        return $payload + [
            'sections' => $sections,
            'structured_plan' => $plan->structured_plan,
            'note' => $revision ? [
                'revision' => $revision->revision,
                'status' => $revision->status,
                'depth' => $revision->depth,
                'content' => $revision->content,
                'html' => $plan->lesson_notes,
                'teacher_edited' => $revision->teacher_edited,
                'validation' => $validation ? [
                    'status' => $validation->status,
                    'plan_coverage' => $validation->plan_coverage,
                    'missing_plan_items' => $validation->missing_plan_items,
                    'suggested_additions' => $validation->suggested_additions,
                ] : null,
            ] : null,
        ];
    }

    private function className(ClassArm $class): string
    {
        $class->loadMissing('classLevel');

        return trim(($class->classLevel?->name ?? '').' '.$class->name);
    }

    private function recordRepositorySources(LessonPlan $plan): void
    {
        foreach (($plan->structured_plan['repository_context'] ?? []) as $rank => $source) {
            if (! empty($source['fragment_id'])) {
                LessonPlanSource::create([
                    'lesson_plan_id' => $plan->id,
                    'curriculum_source_id' => $source['source_id'] ?? 0,
                    'curriculum_fragment_id' => $source['fragment_id'],
                    'rank' => $rank + 1,
                    'generation_type' => 'lesson_plan',
                ]);
            }
        }
    }
}
