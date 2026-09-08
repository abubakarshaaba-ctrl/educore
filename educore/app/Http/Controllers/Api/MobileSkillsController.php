<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassArm;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileSkillsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $classes = $this->accessibleClasses($user)
            ->with(['classLevel:id,name', 'formTutor:id,name'])
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ClassArm $arm): array => [
                'id' => $arm->id,
                'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                'form_tutor' => $arm->formTutor?->name,
                'student_count' => Student::where('tenant_id', $tenantId)
                    ->where('current_class_arm_id', $arm->id)
                    ->where('status', Student::STATUS_ACTIVE)
                    ->count(),
            ]);

        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Term $term): array => [
                'id' => $term->id,
                'name' => $term->name,
                'session_id' => $term->session_id,
                'session' => $term->session?->name,
                'current' => (bool) $term->is_current,
            ]);

        $skills = SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order_index')
            ->orderBy('name')
            ->get()
            ->map(fn (SkillDefinition $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'order' => (int) $skill->order_index,
            ]);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => [
                'manage' => $user->canManage('skills'),
                'all_classes' => $this->canAccessAllClasses($user),
            ],
            'metrics' => [
                'classes' => $classes->count(),
                'students' => $classes->sum('student_count'),
                'skills' => $skills->count(),
                'rated_entries' => StudentSkillRating::where('tenant_id', $tenantId)->count(),
            ],
            'classes' => $classes->values(),
            'terms' => $terms->values(),
            'skills' => $skills->values(),
            'rating_scale' => $this->ratingScale(),
        ]);
    }

    public function sheet(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $data = $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $classArm = ClassArm::where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->findOrFail((int) $data['class_arm_id']);
        $this->authorizeClass($user, $classArm);
        $term = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->findOrFail((int) $data['term_id']);

        $students = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name']);

        $skills = SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'order_index']);

        $ratings = StudentSkillRating::where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('skill_definition_id', $skills->pluck('id'))
            ->get(['student_id', 'skill_definition_id', 'rating'])
            ->groupBy('student_id');

        return response()->json([
            'contract_version' => 1,
            'class' => [
                'id' => $classArm->id,
                'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
            ],
            'term' => [
                'id' => $term->id,
                'name' => $term->name,
                'session_id' => $term->session_id,
                'session' => $term->session?->name,
                'current' => (bool) $term->is_current,
            ],
            'skills' => $skills->map(fn (SkillDefinition $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'order' => (int) $skill->order_index,
            ])->values(),
            'students' => $students->map(function (Student $student) use ($ratings): array {
                $studentRatings = $ratings->get($student->id, collect());
                return [
                    'id' => $student->id,
                    'admission_number' => $student->admission_number,
                    'name' => trim(implode(' ', array_filter([
                        $student->first_name,
                        $student->middle_name,
                        $student->last_name,
                    ]))),
                    'ratings' => $studentRatings->map(fn (StudentSkillRating $rating): array => [
                        'skill_id' => $rating->skill_definition_id,
                        'rating' => (int) $rating->rating,
                    ])->values(),
                ];
            })->values(),
            'rating_scale' => $this->ratingScale(),
            'capabilities' => ['manage' => $user->canManage('skills')],
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $data = $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $tenantId)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $tenantId)],
            'ratings' => ['required', 'array', 'max:500'],
            'ratings.*.student_id' => ['required', 'integer'],
            'ratings.*.skills' => ['required', 'array', 'max:40'],
            'ratings.*.skills.*.skill_id' => ['required', 'integer'],
            'ratings.*.skills.*.rating' => ['nullable', 'integer', 'min:0', 'max:5'],
        ]);

        $classArm = ClassArm::where('tenant_id', $tenantId)->findOrFail((int) $data['class_arm_id']);
        $this->authorizeClass($user, $classArm);
        $term = Term::where('tenant_id', $tenantId)->findOrFail((int) $data['term_id']);

        $allowedStudentIds = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedSkillIds = SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $saved = 0;
        $cleared = 0;
        DB::transaction(function () use (
            $data,
            $term,
            $tenantId,
            $user,
            $allowedStudentIds,
            $allowedSkillIds,
            &$saved,
            &$cleared,
        ): void {
            foreach ($data['ratings'] as $studentRating) {
                $studentId = (int) $studentRating['student_id'];
                if (!in_array($studentId, $allowedStudentIds, true)) {
                    throw ValidationException::withMessages([
                        'ratings' => 'One or more students are not active members of the selected class.',
                    ]);
                }

                foreach ($studentRating['skills'] as $skillRating) {
                    $skillId = (int) $skillRating['skill_id'];
                    if (!in_array($skillId, $allowedSkillIds, true)) {
                        throw ValidationException::withMessages([
                            'ratings' => 'One or more skill definitions are not active for this school.',
                        ]);
                    }

                    $rating = $skillRating['rating'] ?? null;
                    if ($rating === null || (int) $rating === 0) {
                        $cleared += StudentSkillRating::where('tenant_id', $tenantId)
                            ->where('student_id', $studentId)
                            ->where('skill_definition_id', $skillId)
                            ->where('term_id', $term->id)
                            ->delete();
                        continue;
                    }

                    StudentSkillRating::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'student_id' => $studentId,
                            'skill_definition_id' => $skillId,
                            'term_id' => $term->id,
                        ],
                        [
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
            'message' => 'Skill ratings saved successfully.',
            'saved' => $saved,
            'cleared' => $cleared,
        ]);
    }

    private function accessibleClasses(User $user)
    {
        $query = ClassArm::where('tenant_id', $user->tenant_id);
        if (!$this->canAccessAllClasses($user)) {
            $query->where('form_tutor_id', $user->id);
        }

        return $query;
    }

    private function authorizeClass(User $user, ClassArm $classArm): void
    {
        if ($this->canAccessAllClasses($user)) {
            return;
        }

        abort_unless(
            (int) $classArm->form_tutor_id === (int) $user->id,
            403,
            'You can only rate skills for the class arm you are assigned as form teacher.'
        );
    }

    private function canAccessAllClasses(User $user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('students');
    }

    private function ratingScale(): array
    {
        return [
            ['value' => 5, 'label' => 'Excellent'],
            ['value' => 4, 'label' => 'Very Good'],
            ['value' => 3, 'label' => 'Good'],
            ['value' => 2, 'label' => 'Fair'],
            ['value' => 1, 'label' => 'Poor'],
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School skill-rating access required.');
        abort_unless($user->tenant_id, 403, 'School skill-rating access required.');
        abort_unless(
            $manage ? $user->canManage('skills') : $user->canAccessModule('skills'),
            403,
            $manage ? 'Skill-rating management permission required.' : 'Skill-rating access required.'
        );

        return $user;
    }
}
