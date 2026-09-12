<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassArm;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkillsController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $allClasses = $this->canRateAll($user);
        $classes = $this->classesFor($user, $allClasses);
        $classIds = $classes->pluck('id');
        $studentCount = Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->when($classIds->isNotEmpty(), fn ($query) => $query->whereIn('current_class_arm_id', $classIds))
            ->when($classIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
            ->count();

        $skills = $this->skills($tenantId);
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        $ratedEntries = StudentSkillRating::where('tenant_id', $tenantId)
            ->when($classIds->isNotEmpty(), function ($query) use ($classIds) {
                $query->whereHas('student', fn ($students) => $students->whereIn('current_class_arm_id', $classIds));
            })
            ->when($classIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
            ->count();

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => true, 'all_classes' => $allClasses],
            'metrics' => [
                'classes' => $classes->count(),
                'students' => $studentCount,
                'skills' => $skills->count(),
                'rated_entries' => $ratedEntries,
            ],
            'classes' => $classes->map(fn (ClassArm $classArm) => [
                'id' => $classArm->id,
                'name' => $classArm->full_name,
                'form_tutor' => $classArm->formTutor?->name,
                'student_count' => (int) $classArm->students_count,
            ])->values(),
            'terms' => $terms->map(fn (Term $term) => $this->termPayload($term))->values(),
            'skills' => $skills->map(fn (SkillDefinition $skill) => $this->skillPayload($skill))->values(),
            'rating_scale' => $this->ratingScale(),
        ]);
    }

    public function sheet(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ]);
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        SkillDefinition::ensureDefaultsForTenant($tenantId);

        $classArm = ClassArm::where('tenant_id', $tenantId)
            ->with(['classLevel:id,name', 'formTutor:id,name'])
            ->findOrFail((int) $data['class_arm_id']);
        $this->assertClassAccess($user, $classArm);
        $term = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->findOrFail((int) $data['term_id']);

        $skills = $this->skills($tenantId);
        $students = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $ratings = StudentSkillRating::where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('skill_definition_id', $skills->pluck('id'))
            ->get()
            ->groupBy('student_id');

        return response()->json([
            'contract_version' => 1,
            'class' => ['id' => $classArm->id, 'name' => $classArm->full_name],
            'term' => $this->termPayload($term),
            'skills' => $skills->map(fn (SkillDefinition $skill) => $this->skillPayload($skill))->values(),
            'students' => $students->map(function (Student $student) use ($ratings) {
                $studentRatings = $ratings->get($student->id, collect());

                return [
                    'id' => $student->id,
                    'admission_number' => $student->admission_number,
                    'name' => $student->full_name,
                    'ratings' => $studentRatings->map(fn (StudentSkillRating $rating) => [
                        'skill_id' => $rating->skill_definition_id,
                        'rating' => (int) $rating->rating,
                    ])->values(),
                ];
            })->values(),
            'rating_scale' => $this->ratingScale(),
            'capabilities' => ['manage' => true, 'all_classes' => $this->canRateAll($user)],
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'ratings' => ['required', 'array'],
            'ratings.*.student_id' => ['required', 'integer'],
            'ratings.*.skills' => ['required', 'array'],
            'ratings.*.skills.*.skill_id' => ['required', 'integer'],
            'ratings.*.skills.*.rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $classArm = ClassArm::where('tenant_id', $tenantId)->findOrFail((int) $data['class_arm_id']);
        $this->assertClassAccess($user, $classArm);
        $term = Term::where('tenant_id', $tenantId)->findOrFail((int) $data['term_id']);

        $students = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $skillIds = $this->skills($tenantId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $saved = 0;
        $cleared = 0;
        DB::transaction(function () use ($data, $tenantId, $term, $user, $students, $skillIds, &$saved, &$cleared) {
            foreach ($data['ratings'] as $studentPayload) {
                $studentId = (int) $studentPayload['student_id'];
                abort_unless(in_array($studentId, $students, true), 422, 'One or more students do not belong to the selected class.');

                foreach ($studentPayload['skills'] as $skillPayload) {
                    $skillId = (int) $skillPayload['skill_id'];
                    abort_unless(in_array($skillId, $skillIds, true), 422, 'One or more selected skills are not available.');
                    $rating = $skillPayload['rating'] ?? null;

                    if ($rating === null) {
                        $cleared += StudentSkillRating::where('tenant_id', $tenantId)
                            ->where('student_id', $studentId)
                            ->where('skill_definition_id', $skillId)
                            ->where('term_id', $term->id)
                            ->delete();
                        continue;
                    }

                    StudentSkillRating::updateOrCreate([
                        'tenant_id' => $tenantId,
                        'student_id' => $studentId,
                        'skill_definition_id' => $skillId,
                        'term_id' => $term->id,
                    ], [
                        'session_id' => $term->session_id,
                        'rating' => (int) $rating,
                        'rated_by' => $user->id,
                    ]);
                    $saved++;
                }
            }
        });

        return response()->json([
            'message' => 'Skills ratings saved successfully.',
            'saved' => $saved,
            'cleared' => $cleared,
        ]);
    }

    private function guard(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isAccountant(), 403, 'Accountants cannot manage skills ratings.');
        abort_unless(
            $this->canRateAll($user) || $user->canAccessExactModule('skills') || $user->canAccessExactModule('skills.rating') || $this->isFormTutor($user),
            403,
            'You do not have permission to manage skills ratings.'
        );

        return $user;
    }

    private function canRateAll(User $user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('skills');
    }

    private function isFormTutor(User $user): bool
    {
        return ClassArm::where('tenant_id', $user->tenant_id)->where('form_tutor_id', $user->id)->exists();
    }

    private function classesFor(User $user, bool $allClasses)
    {
        return ClassArm::where('tenant_id', $user->tenant_id)
            ->with(['classLevel:id,name', 'formTutor:id,name'])
            ->withCount(['students' => fn ($query) => $query->where('status', Student::STATUS_ACTIVE)])
            ->when(! $allClasses, fn ($query) => $query->where('form_tutor_id', $user->id))
            ->get()
            ->sortBy(fn (ClassArm $classArm) => $classArm->full_name)
            ->values();
    }

    private function assertClassAccess(User $user, ClassArm $classArm): void
    {
        abort_unless($this->canRateAll($user) || (int) $classArm->form_tutor_id === (int) $user->id, 403, 'You can only rate students in your assigned form class.');
    }

    private function skills(int $tenantId)
    {
        return SkillDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();
    }

    private function skillPayload(SkillDefinition $skill): array
    {
        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'category' => $skill->category,
            'order' => (int) $skill->order_index,
        ];
    }

    private function termPayload(Term $term): array
    {
        return [
            'id' => $term->id,
            'name' => $term->name,
            'session_id' => $term->session_id,
            'session' => $term->session?->name,
            'current' => (bool) $term->is_current,
        ];
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
}
