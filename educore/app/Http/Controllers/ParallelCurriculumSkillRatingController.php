<?php

namespace App\Http\Controllers;

use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumSkillRating;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\Term;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ParallelCurriculumSkillRatingController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $this->tenantId();

        abort_unless(
            $user && $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            Schema::hasTable('parallel_curriculum_skill_ratings'),
            503,
            'Parallel skill ratings are unavailable until the latest database migration has been applied.'
        );

        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $canManage = $this->parallel->canManageLifecycle($user);
        $arms = $canManage
            ? ParallelCurriculumClassArm::with([
                    'curriculumClass.curriculum',
                    'classTeacher',
                ])
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
            ->orderByDesc('id')
            ->get();

        $currentTerm = $terms->firstWhere('is_current', true);
        $termId = (int) (
            $request->integer('term_id')
            ?: ($currentTerm?->id ?? $terms->first()?->id ?? 0)
        );
        $armId = (int) (
            $request->integer('arm_id')
            ?: ($arms->first()?->id ?? 0)
        );

        $term = $termId ? $terms->firstWhere('id', $termId) : null;
        $arm = $armId ? $arms->firstWhere('id', $armId) : null;

        abort_if($termId && ! $term, 404, 'The selected term is unavailable.');
        abort_if($armId && ! $arm, 403, 'You cannot rate the selected parallel class arm.');

        $enrolments = collect();
        $psychomotorSkills = SkillDefinition::psychomotor()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        $affectiveSkills = SkillDefinition::affective()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        $existingRatings = collect();

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

            $existingRatings = ParallelCurriculumSkillRating::where(
                    'tenant_id',
                    $tenantId
                )
                ->where('term_id', $term->id)
                ->whereIn(
                    'parallel_curriculum_enrolment_id',
                    $enrolments->pluck('id')
                )
                ->get()
                ->groupBy('parallel_curriculum_enrolment_id')
                ->map(fn ($ratings) => $ratings->keyBy('skill_definition_id'));
        }

        return view('parallel-curriculum.skills.index', compact(
            'arms',
            'terms',
            'currentTerm',
            'armId',
            'termId',
            'arm',
            'term',
            'enrolments',
            'psychomotorSkills',
            'affectiveSkills',
            'existingRatings',
            'canManage'
        ));
    }

    public function save(Request $request)
    {
        $user = $request->user();
        $tenantId = $this->tenantId();

        abort_unless(
            $user && $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            Schema::hasTable('parallel_curriculum_skill_ratings'),
            503,
            'Parallel skill ratings are unavailable until the latest database migration has been applied.'
        );

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
            'ratings' => ['nullable', 'array'],
            'ratings.*' => ['array'],
            'ratings.*.*' => ['nullable', 'integer', 'between:1,5'],
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

        DB::transaction(function () use (
            $data,
            $user,
            $tenantId,
            $arm,
            $term,
            $enrolments,
            $allowedSkillIds
        ): void {
            foreach (($data['ratings'] ?? []) as $enrolmentId => $skills) {
                $enrolment = $enrolments->get((int) $enrolmentId);
                abort_unless($enrolment, 422, 'One or more learners are not active in the selected parallel class arm.');

                foreach ($skills as $skillId => $rating) {
                    abort_unless(
                        in_array((int) $skillId, $allowedSkillIds, true),
                        422,
                        'One or more submitted skill definitions are unavailable.'
                    );

                    if ($rating === null || $rating === '') {
                        ParallelCurriculumSkillRating::where(
                                'parallel_curriculum_enrolment_id',
                                $enrolment->id
                            )
                            ->where('skill_definition_id', (int) $skillId)
                            ->where('term_id', $term->id)
                            ->delete();
                        continue;
                    }

                    ParallelCurriculumSkillRating::updateOrCreate(
                        [
                            'parallel_curriculum_enrolment_id' => $enrolment->id,
                            'skill_definition_id' => (int) $skillId,
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
                }
            }
        });

        return redirect()
            ->route('parallel-curriculum.skills.index', [
                'arm_id' => $arm->id,
                'term_id' => $term->id,
            ])
            ->with('success', 'Parallel curriculum skill ratings saved.');
    }
}
