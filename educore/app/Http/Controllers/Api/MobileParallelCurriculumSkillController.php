<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSkillRating;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\Term;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MobileParallelCurriculumSkillController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
    ) {}

    public function index(Request $request): JsonResponse
    {
        [$user, $tenantId] = $this->guard($request);

        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $canManage = $this->parallel->canManageLifecycle($user);
        $arms = $canManage
            ? ParallelCurriculumClassArm::with('curriculumClass.curriculum')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereHas('curriculumClass', function ($query): void {
                    $query->where('is_active', true)
                        ->whereHas(
                            'curriculum',
                            fn ($curriculum) => $curriculum->where('is_active', true)
                        );
                })
                ->orderBy('parallel_curriculum_class_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : $this->parallel->formTeacherArmsForUser($user);

        abort_unless(
            $canManage || $arms->isNotEmpty(),
            403,
            'You are not assigned as a parallel curriculum form teacher.'
        );

        $terms = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $currentTerm = $terms->firstWhere('is_current', true) ?: $terms->first();
        $armId = (int) ($request->integer('arm_id') ?: ($arms->first()?->id ?? 0));
        $termId = (int) ($request->integer('term_id') ?: ($currentTerm?->id ?? 0));

        $arm = $armId ? $arms->firstWhere('id', $armId) : null;
        $term = $termId ? $terms->firstWhere('id', $termId) : null;

        abort_if($armId && ! $arm, 403, 'You cannot rate the selected parallel class arm.');
        abort_if($termId && ! $term, 404, 'The selected term is unavailable.');

        $skills = SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN category = 'affective' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $enrolments = collect();
        $ratings = collect();

        if ($arm && $term) {
            $enrolments = ParallelCurriculumEnrolment::with('student')
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('session_id', $term->session_id)
                ->where('is_active', true)
                ->whereHas(
                    'student',
                    fn ($query) => $query->where('status', Student::STATUS_ACTIVE)
                )
                ->get()
                ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) =>
                    strtolower(
                        ($enrolment->student?->last_name ?? '').' '.
                        ($enrolment->student?->first_name ?? '')
                    )
                )
                ->values();

            $ratings = ParallelCurriculumSkillRating::where('tenant_id', $tenantId)
                ->where('term_id', $term->id)
                ->whereIn(
                    'parallel_curriculum_enrolment_id',
                    $enrolments->pluck('id')
                )
                ->get()
                ->groupBy('parallel_curriculum_enrolment_id')
                ->map(fn ($rows) => $rows->keyBy('skill_definition_id'));
        }

        return response()->json([
            'contract_version' => 1,
            'selected_arm_id' => $arm?->id,
            'selected_term_id' => $term?->id,
            'arms' => $arms->map(fn (ParallelCurriculumClassArm $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'class_id' => (int) $item->parallel_curriculum_class_id,
                'class_name' => $item->curriculumClass?->name,
                'curriculum_id' => (int) ($item->curriculumClass?->parallel_curriculum_id ?? 0),
                'curriculum_name' => $item->curriculumClass?->curriculum?->name,
                'label' => trim(
                    ($item->curriculumClass?->curriculum?->name ?: 'Parallel').' · '.
                    ($item->curriculumClass?->name ?: 'Class').' '.$item->name
                ),
            ])->values(),
            'terms' => $terms->map(fn (Term $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'session_id' => (int) $item->session_id,
                'session_name' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
                'label' => trim(($item->session?->name ?: 'Session').' · '.$item->name),
            ])->values(),
            'skills' => $skills->map(fn (SkillDefinition $skill) => [
                'id' => (int) $skill->id,
                'name' => (string) $skill->name,
                'category' => (string) $skill->category,
                'sort_order' => (int) $skill->sort_order,
            ])->values(),
            'students' => $enrolments->map(function (
                ParallelCurriculumEnrolment $enrolment
            ) use ($ratings): array {
                $studentRatings = $ratings->get($enrolment->id, collect());

                return [
                    'enrolment_id' => (int) $enrolment->id,
                    'student_id' => (int) $enrolment->student_id,
                    'name' => $enrolment->student?->full_name ?? 'Student',
                    'admission_number' => $enrolment->student?->admission_number,
                    'ratings' => $studentRatings->map(fn (ParallelCurriculumSkillRating $rating) => [
                        'skill_id' => (int) $rating->skill_definition_id,
                        'rating' => (int) $rating->rating,
                    ])->values(),
                ];
            })->values(),
            'rating_scale' => [
                ['value' => 5, 'label' => 'Excellent'],
                ['value' => 4, 'label' => 'Very Good'],
                ['value' => 3, 'label' => 'Good'],
                ['value' => 2, 'label' => 'Fair'],
                ['value' => 1, 'label' => 'Poor'],
            ],
            'capabilities' => [
                'manage' => $canManage || ($arm ? $this->parallel->isFormTeacherForArm($user, $arm) : false),
            ],
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        [$user, $tenantId] = $this->guard($request);

        $data = $request->validate([
            'arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'ratings' => ['required', 'array', 'max:500'],
            'ratings.*.enrolment_id' => ['required', 'integer'],
            'ratings.*.skills' => ['required', 'array', 'max:40'],
            'ratings.*.skills.*.skill_id' => ['required', 'integer'],
            'ratings.*.skills.*.rating' => ['nullable', 'integer', 'between:0,5'],
        ]);

        $arm = ParallelCurriculumClassArm::with('curriculumClass.curriculum')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail((int) $data['arm_id']);
        $term = Term::where('tenant_id', $tenantId)
            ->findOrFail((int) $data['term_id']);

        abort_unless(
            $this->parallel->canManageLifecycle($user)
                || $this->parallel->isFormTeacherForArm($user, $arm),
            403,
            'Only the assigned parallel form teacher or an academic administrator can rate this class.'
        );

        $enrolments = ParallelCurriculumEnrolment::where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $allowedSkillIds = SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $saved = 0;
        $cleared = 0;

        DB::transaction(function () use (
            $data,
            $user,
            $tenantId,
            $arm,
            $term,
            $enrolments,
            $allowedSkillIds,
            &$saved,
            &$cleared
        ): void {
            foreach ($data['ratings'] as $studentRating) {
                $enrolment = $enrolments->get((int) $studentRating['enrolment_id']);
                abort_unless(
                    $enrolment,
                    422,
                    'One or more learners are not active in the selected parallel class arm.'
                );

                foreach ($studentRating['skills'] as $skillRating) {
                    $skillId = (int) $skillRating['skill_id'];
                    abort_unless(
                        in_array($skillId, $allowedSkillIds, true),
                        422,
                        'One or more skill definitions are unavailable.'
                    );

                    $rating = $skillRating['rating'] ?? null;
                    if ($rating === null || (int) $rating === 0) {
                        $cleared += ParallelCurriculumSkillRating::where(
                                'parallel_curriculum_enrolment_id',
                                $enrolment->id
                            )
                            ->where('skill_definition_id', $skillId)
                            ->where('term_id', $term->id)
                            ->delete();
                        continue;
                    }

                    ParallelCurriculumSkillRating::updateOrCreate(
                        [
                            'parallel_curriculum_enrolment_id' => $enrolment->id,
                            'skill_definition_id' => $skillId,
                            'term_id' => $term->id,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'parallel_curriculum_id' => $enrolment->parallel_curriculum_id,
                            'parallel_curriculum_class_id' => $enrolment->parallel_curriculum_class_id,
                            'parallel_curriculum_class_arm_id' => $arm->id,
                            'student_id' => $enrolment->student_id,
                            'session_id' => $term->session_id,
                            'rating' => (int) $rating,
                            'rated_by' => $user->id,
                        ]
                    );
                    $saved++;
                }
            }
        });

        return response()->json([
            'message' => 'Parallel curriculum skill ratings saved.',
            'saved' => $saved,
            'cleared' => $cleared,
        ]);
    }

    private function guard(Request $request): array
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id, 403, 'School staff access required.');

        $tenantId = (int) $user->tenant_id;
        abort_unless(
            $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            Schema::hasTable('parallel_curriculum_skill_ratings'),
            503,
            'Parallel skill ratings are unavailable until the latest database migration has been applied.'
        );

        return [$user, $tenantId];
    }
}
