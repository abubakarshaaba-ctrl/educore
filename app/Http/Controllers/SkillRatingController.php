<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SkillRatingController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    // ---------------------------------------------------------------
    // SKILL RATING ENTRY — select class/term then rate all students
    // ---------------------------------------------------------------
    public function index()
    {
        $user = auth()->user();

        $isAdminTier = $user->canAccessExactModule('students') || $user->isSuperAdmin();
        if ($isAdminTier) {
            $classArms = ClassArm::with('classLevel')->get();
        } else {
            // Only the assigned form teacher can rate skills — not regular subject teachers
            $classArms = ClassArm::with('classLevel')
                ->where('form_tutor_id', $user->id)
                ->get();
        }

        $terms = Term::with('session')->latest()->get();
        return view('skills.index', compact('classArms', 'terms'));
    }

    public function sheet(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'      => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);

        $skillUser = auth()->user();
        if (!$skillUser->canAccessExactModule('students') && !$skillUser->isSuperAdmin()) {
            abort_if(
                $classArm->form_tutor_id !== $skillUser->id,
                403,
                'You can only rate skills for the class arm you are assigned as form teacher.'
            );
        }

        $term     = Term::with('session')->findOrFail($request->term_id);

        $students = Student::where('current_class_arm_id', $classArm->id)
                           ->where('status', Student::STATUS_ACTIVE)
                           ->orderBy('last_name')
                           ->get();

        $psychomotorSkills = SkillDefinition::psychomotor()->where('is_active', true)->get();
        $affectiveSkills   = SkillDefinition::affective()->where('is_active', true)->get();

        // Load existing ratings keyed by student_id → skill_id
        $existingRatings = StudentSkillRating::whereIn('student_id', $students->pluck('id'))
            ->where('term_id', $term->id)
            ->get()
            ->groupBy('student_id')
            ->map(fn($ratings) => $ratings->keyBy('skill_definition_id'));

        return view('skills.sheet', compact(
            'classArm', 'term', 'students',
            'psychomotorSkills', 'affectiveSkills', 'existingRatings'
        ));
    }

    // ---------------------------------------------------------------
    // SAVE RATINGS (bulk)
    // ---------------------------------------------------------------
    public function save(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'      => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'ratings'      => ['required', 'array'],
        ]);

        $classArm = ClassArm::findOrFail($request->class_arm_id);

        $saveUser = auth()->user();
        if (!$saveUser->canAccessExactModule('students') && !$saveUser->isSuperAdmin()) {
            abort_if(
                $classArm->form_tutor_id !== $saveUser->id,
                403,
                'You can only save skill ratings for the class arm you are assigned as form teacher.'
            );
        }

        $term = Term::findOrFail($request->term_id);
        $allowedStudentIds = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedSkillIds = SkillDefinition::where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($request, $term, $allowedStudentIds, $allowedSkillIds) {
            foreach ($request->ratings as $studentId => $skills) {
                abort_unless(in_array((int) $studentId, $allowedStudentIds, true), 403);
                foreach ($skills as $skillId => $rating) {
                    abort_unless(in_array((int) $skillId, $allowedSkillIds, true), 403);
                    if (!$rating || $rating < 1 || $rating > 5) continue;

                    StudentSkillRating::updateOrCreate(
                        [
                            'student_id'          => $studentId,
                            'skill_definition_id' => $skillId,
                            'term_id'             => $request->term_id,
                        ],
                        [
                            'session_id' => $term->session_id,
                            'rating'     => (int)$rating,
                            'rated_by'   => Auth::id(),
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Skill ratings saved successfully.');
    }
}
