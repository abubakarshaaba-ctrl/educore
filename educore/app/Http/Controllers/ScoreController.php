<?php

namespace App\Http\Controllers;

use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScoreController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    /** True if this user has unrestricted score access (admin tier), not just scoped entry/view. */
    private function hasFullScoreAccess($user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('scores');
    }

    /** Admin discriminator: has 'students' module (true admin-tier) OR is super-admin. */
    private function isAdminTier($user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('students');
    }

    /**
     * Verify user can view/enter scores for a specific class:
     * must be assigned to teach a subject there, OR be the form tutor.
     */
    private function assertTeachesClassSubject($user, int $classArmId, int $subjectId): void
    {
        if ($this->hasFullScoreAccess($user)) return;
        $assigned = \App\Models\ClassArmSubject::where('teacher_id', $user->id)
            ->where('class_arm_id', $classArmId)
            ->where('subject_id', $subjectId)
            ->exists();
        if (!$assigned) abort(403, 'You are not assigned to teach this subject in this class.');
    }

    /**
     * Verify user can view the broadsheet for a class:
     * must be the FORM TUTOR of this class specifically.
     * Subject teachers cannot view the broadsheet (they don't have scores.view anyway).
     */
    private function assertTeachesOrTutorsClass($user, int $classArmId): void
    {
        if ($this->hasFullScoreAccess($user)) return;

        $isFormTutor = ClassArm::where('id', $classArmId)
            ->where('form_tutor_id', $user->id)->exists();

        if (!$isFormTutor) {
            abort(403, 'You can only view the broadsheet for your assigned form class.');
        }
    }

    /**
     * For form-teacher style broadsheet viewing: verify the requested class is
     * one this user is the form tutor of (or that they have full access).
     */
    private function assertOwnsFormClass($user, int $classArmId): void
    {
        $this->assertTeachesOrTutorsClass($user, $classArmId);
    }

    // ---------------------------------------------------------------
    // SCORE INDEX — Select class/subject/term to open entry sheet
    // ---------------------------------------------------------------
    public function index()
    {
        $user      = auth()->user();
        $terms     = Term::with('session')->latest()->get();
        $currentTerm = $terms->firstWhere('is_current', true);

        // Determine scoped classArms early so progress cards are also scoped.
        $teacherScoped = !$this->hasFullScoreAccess($user)
            && ($user->canAccessExactModule('scores.entry') || $user->canAccessExactModule('scores.view'));

        if ($teacherScoped) {
            $subjectIds   = \App\Models\ClassArmSubject::where('teacher_id', $user->id)->pluck('subject_id')->unique();
            $subjects     = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
            $taughtArmIds = \App\Models\ClassArmSubject::where('teacher_id', $user->id)->pluck('class_arm_id')->unique();
            $formArmIds   = ClassArm::where('form_tutor_id', $user->id)->pluck('id');
            $classArmIds  = $taughtArmIds->merge($formArmIds)->unique();
            $classArms    = ClassArm::with('classLevel')->whereIn('id', $classArmIds)->get();
        } else {
            $classArms = ClassArm::with('classLevel')->get();
            $subjects  = Subject::where('is_active', true)->orderBy('name')->get();
        }

        // ── Score Entry Progress per Class ─────────────────────────────
        $progress = [];
        if ($currentTerm) {
            foreach ($classArms as $arm) {
                // Count expected scores: students × subjects × assessment types
                $studentCount = \App\Models\Student::where('current_class_arm_id', $arm->id)
                                    ->where('status', Student::STATUS_ACTIVE)->count();
                $subjectCount = \App\Models\ClassArmSubject::where('class_arm_id', $arm->id)
                                    ->count();
                $assessTypeCount = \App\Models\AssessmentType::where('term_id', $currentTerm->id)->count();

                $expected = $studentCount * $subjectCount * $assessTypeCount;
                $entered  = \App\Models\Score::where('term_id', $currentTerm->id)
                                ->whereIn('student_id',
                                    \App\Models\Student::where('current_class_arm_id', $arm->id)
                                        ->where('status', Student::STATUS_ACTIVE)
                                        ->pluck('id'))
                                ->count();

                if ($expected > 0) {
                    $progress[$arm->id] = [
                        'arm'       => $arm,
                        'expected'  => $expected,
                        'entered'   => $entered,
                        'pct'       => min(100, round(($entered / $expected) * 100)),
                        'students'  => $studentCount,
                        'subjects'  => $subjectCount,
                    ];
                }
            }
        }

        return view('scores.index', compact('currentTerm', 'progress', 'classArms', 'terms', 'subjects'));
    }

    // ---------------------------------------------------------------
    // SCORE ENTRY SHEET
    // Shows ALL assessment types as columns for a class/subject/term.
    // Teacher fills in all assessments on one sheet.
    // ---------------------------------------------------------------
    public function entry(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'subject_id'   => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'      => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $subject  = Subject::findOrFail($request->subject_id);
        $term     = Term::with('session')->findOrFail($request->term_id);

        $this->assertTeachesClassSubject(auth()->user(), $classArm->id, $subject->id);

        // All students in this class
        $students = Student::where('current_class_arm_id', $classArm->id)
                           ->where('status', Student::STATUS_ACTIVE)
                           ->orderBy('last_name')
                           ->get();

        // All assessment types for this term — CAs/tests first (is_exam=0), exam last (is_exam=1),
        // within each group ordered by weight ascending so CA1 < CA2 < TEST1 < TEST2 < EXAM
        $assessmentTypes = AssessmentType::where('term_id', $term->id)
                                         ->orderBy('is_exam')
                                         ->orderBy('weight_percentage')
                                         ->orderBy('name')
                                         ->get();

        // Existing scores for all students + all assessment types for this subject/term
        // Keyed as [student_id][assessment_type_id] => score value
        $existingScores = [];
        if ($students->isNotEmpty() && $assessmentTypes->isNotEmpty()) {
            Score::whereIn('student_id', $students->pluck('id'))
                 ->where('subject_id', $subject->id)
                 ->where('term_id', $term->id)
                 ->get()
                 ->each(function ($score) use (&$existingScores) {
                     $existingScores[$score->student_id][$score->assessment_type_id] = $score->score;
                 });
        }

        // Per-student subject totals (sum of all assessments)
        $studentTotals = [];
        foreach ($students as $student) {
            $total = 0;
            foreach ($assessmentTypes as $at) {
                $total += $existingScores[$student->id][$at->id] ?? 0;
            }
            $studentTotals[$student->id] = $total;
        }

        return view('scores.entry', compact(
            'classArm', 'subject', 'term',
            'students', 'assessmentTypes',
            'existingScores', 'studentTotals'
        ));
    }

    // ---------------------------------------------------------------
    // SAVE SCORES — All assessments for a class/subject/term at once
    // ---------------------------------------------------------------
    public function save(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'subject_id'   => ['required', Rule::exists('subjects', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'      => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'scores'       => ['required', 'array'],
        ]);

        $classArm = ClassArm::findOrFail($request->class_arm_id);
        $term    = Term::findOrFail($request->term_id);
        $tenantId = auth()->user()->tenant_id;

        $this->assertTeachesClassSubject(auth()->user(), $classArm->id, (int) $request->subject_id);
        $allowedStudentIds = Student::where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedAssessmentTypeIds = AssessmentType::where('term_id', $term->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($request, $term, $tenantId, $allowedStudentIds, $allowedAssessmentTypeIds) {
            // scores[student_id][assessment_type_id] = value
            foreach ($request->scores as $studentId => $assessments) {
                abort_unless(in_array((int) $studentId, $allowedStudentIds, true), 403);

                foreach ($assessments as $assessmentTypeId => $scoreValue) {
                    abort_unless(in_array((int) $assessmentTypeId, $allowedAssessmentTypeIds, true), 403);
                    if ($scoreValue === null || $scoreValue === '') continue;

                    $at = AssessmentType::find($assessmentTypeId);
                    if (!$at) continue;

                    $capped = min((float)$scoreValue, $at->weight_percentage);

                    Score::updateOrCreate(
                        [
                            'student_id'         => $studentId,
                            'subject_id'         => $request->subject_id,
                            'assessment_type_id' => $assessmentTypeId,
                            'term_id'            => $request->term_id,
                        ],
                        [
                            'session_id' => $term->session_id,
                            'score'      => $capped,
                            'entered_by' => Auth::id(),
                            'entered_at' => now(),
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Scores saved successfully.');
    }

    // ---------------------------------------------------------------
    // BROADSHEET — Class result overview for all subjects
    // ---------------------------------------------------------------
    public function broadsheet(Request $request)
    {
        $user = auth()->user();

        // Subject teachers with scores.entry only cannot view broadsheet.
        // Only form teachers (scores.view) and admin (scores) have access.
        if (!$user->canAccessExactModule('scores.view') && !$this->hasFullScoreAccess($user)) {
            abort(403, 'You do not have access to the broadsheet. Only form teachers and administrators can view it.');
        }

        if ($this->hasFullScoreAccess($user)) {
            $classArms = ClassArm::with('classLevel')->get();
        } else {
            // Form teachers: broadsheet is for THEIR FORM CLASS only.
            // They should not see other arms just because they teach a subject there.
            // Subject-only teachers (scores.entry) are already blocked above.
            $formArmIds = ClassArm::where('form_tutor_id', $user->id)->pluck('id');

            if ($formArmIds->isEmpty()) {
                // No form class assigned — nothing to show in broadsheet.
                $classArms = collect();
            } else {
                $classArms = ClassArm::with('classLevel')->whereIn('id', $formArmIds)->get();
            }
        }
        $terms     = Term::with('session')->latest()->get();

        if (!$request->filled('class_arm_id') || !$request->filled('term_id')) {
            return view('scores.broadsheet', compact('classArms', 'terms'));
        }

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term     = Term::with('session')->findOrFail($request->term_id);

        // Scoped users can view broadsheet for classes they're either form tutor of
        // OR assigned to teach a subject in (covers subject-only teachers with no form class).
        $this->assertTeachesOrTutorsClass(auth()->user(), $classArm->id);

        $students = Student::where('current_class_arm_id', $classArm->id)
                           ->where('status', Student::STATUS_ACTIVE)
                           ->orderBy('last_name')
                           ->get();

        // Subjects that have scores for this class/term
        $studentIds = $students->pluck('id');

        $allScores = Score::whereIn('student_id', $studentIds)->where('term_id', $term->id)->get();

        // Load subjects that have scores for this class/term, using subject_id from scores
        $scoredSubjectIds = $allScores->pluck('subject_id')->unique()->values();
        $subjects = Subject::whereIn('id', $scoredSubjectIds)->orderBy('name')->get();

        // Fallback: if some scored subject_ids are not in the subjects table (e.g. subject
        // was deleted/recreated), eagerly load subjects via score relation to match by name/code.
        $foundIds = $subjects->pluck('id');
        $missingIds = $scoredSubjectIds->diff($foundIds);
        if ($missingIds->isNotEmpty()) {
            // Load all current subjects for name/code matching
            $allCurrentSubjects = Subject::orderBy('name')->get()->keyBy('id');
            $subjectByName = Subject::orderBy('name')->get()->keyBy(fn($s) => strtolower(trim($s->name)));
            // For each missing ID, find what subject name was used by loading the score relation
            $idRemap = []; // old_id → new_id
            foreach ($missingIds as $oldId) {
                $sampleScore = Score::where('subject_id', $oldId)->with('subject')->first();
                $subName = strtolower(trim($sampleScore?->subject?->name ?? ''));
                if ($subName && isset($subjectByName[$subName])) {
                    $newSubject = $subjectByName[$subName];
                    $idRemap[$oldId] = $newSubject->id;
                    if (!$subjects->contains('id', $newSubject->id)) {
                        $subjects->push($newSubject);
                    }
                }
            }
            // Remap subject_id in $allScores so the matrix lookup works
            if ($idRemap) {
                foreach ($allScores as $score) {
                    if (isset($idRemap[$score->subject_id])) {
                        $score->subject_id = $idRemap[$score->subject_id];
                    }
                }
                $subjects = $subjects->sortBy('name')->values();
            }
        }

        $assessmentTypes = AssessmentType::where('term_id', $term->id)
                                         ->orderBy('is_exam')
                                         ->orderBy('weight_percentage')
                                         ->orderBy('name')
                                         ->get();
        $gradingSystem = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        // Pre-compute per-subject totals for all students (used for class stats)
        // $subjectAllTotals[subject_id] = [student_id => total, ...]
        $subjectAllTotals = [];
        foreach ($subjects as $subject) {
            foreach ($studentIds as $sid) {
                $t = $allScores->where('student_id', $sid)->where('subject_id', $subject->id)->sum('score');
                if ($t > 0) $subjectAllTotals[$subject->id][$sid] = round($t, 1);
            }
        }

        // Class stats per subject (highest, lowest, avg)
        $subjectStats = [];
        foreach ($subjects as $subject) {
            $totals = $subjectAllTotals[$subject->id] ?? [];
            $subjectStats[$subject->id] = [
                'highest' => $totals ? round(max($totals), 1) : '—',
                'lowest'  => $totals ? round(min($totals), 1) : '—',
                'avg'     => $totals ? round(array_sum($totals) / count($totals), 1) : '—',
            ];
        }

        $matrix = [];
        foreach ($students as $student) {
            $row = ['student' => $student, 'subjects' => [], 'total' => 0, 'average' => 0, 'position' => 0];
            $scoredSubjectCount = 0;

            foreach ($subjects as $subject) {
                $subjectScores = $allScores->where('student_id', $student->id)->where('subject_id', $subject->id);
                $hasScores     = $subjectScores->isNotEmpty();
                $subjectTotal  = $hasScores ? round($subjectScores->sum('score'), 1) : null;
                $grade         = $hasScores ? $gradingSystem->filter(fn($g) => $subjectTotal >= $g->min_score && $subjectTotal <= $g->max_score)->first() : null;

                $row['subjects'][$subject->id] = [
                    'scores'     => $subjectScores->keyBy('assessment_type_id'),
                    'has_scores' => $hasScores,
                    'total'      => $subjectTotal,
                    'grade'      => $grade?->grade_letter ?? '—',
                    'remark'     => $grade?->remark ?? '—',
                    'is_pass'    => $grade?->is_pass_grade ?? false,
                ];
                if ($hasScores) {
                    $row['total'] += $subjectTotal;
                    $scoredSubjectCount++;
                }
            }

            $row['average'] = $scoredSubjectCount > 0 ? round($row['total'] / $scoredSubjectCount, 1) : 0;
            $matrix[$student->id] = $row;
        }

        // Rank with tie-handling (same avg → same position)
        uasort($matrix, fn($a, $b) => $b['average'] <=> $a['average']);
        $pos = 1; $prevAvg = null; $prevPos = 1;
        foreach ($matrix as &$row) {
            if ($row['average'] === $prevAvg) {
                $row['position'] = $prevPos;
            } else {
                $row['position'] = $pos;
                $prevPos = $pos;
                $prevAvg = $row['average'];
            }
            $pos++;
        }
        unset($row);

        return view('scores.broadsheet', compact(
            'classArms', 'terms', 'classArm', 'term',
            'students', 'subjects', 'assessmentTypes', 'matrix', 'subjectStats', 'gradingSystem'
        ));
    }

    // ---------------------------------------------------------------
    // BROADSHEET PDF
    // ---------------------------------------------------------------
    public function broadsheetPdf(Request $request)
    {
        $request->validate([
            'class_arm_id' => 'required|integer',
            'term_id'      => 'required|integer',
        ]);

        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term     = Term::with('session')->findOrFail($request->term_id);

        $this->assertTeachesOrTutorsClass(auth()->user(), $classArm->id);

        $students = Student::where('current_class_arm_id', $classArm->id)
                           ->where('status', Student::STATUS_ACTIVE)
                           ->orderBy('last_name')->get();
        $studentIds = $students->pluck('id');

        $subjects = Subject::whereIn('id',
            Score::whereIn('student_id', $studentIds)->where('term_id', $term->id)
                 ->pluck('subject_id')->unique()
        )->orderBy('name')->get();

        $allScores     = Score::whereIn('student_id', $studentIds)->where('term_id', $term->id)->get();
        $gradingSystem = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        $subjectStats = [];
        foreach ($subjects as $subject) {
            $totals = [];
            foreach ($studentIds as $sid) {
                $t = $allScores->where('student_id', $sid)->where('subject_id', $subject->id)->sum('score');
                if ($t > 0) $totals[$sid] = round($t, 1);
            }
            $subjectStats[$subject->id] = [
                'highest' => $totals ? round(max($totals), 1) : '—',
                'lowest'  => $totals ? round(min($totals), 1) : '—',
                'avg'     => $totals ? round(array_sum($totals) / count($totals), 1) : '—',
            ];
        }

        $matrix = [];
        foreach ($students as $student) {
            $row = ['student' => $student, 'subjects' => [], 'total' => 0, 'average' => 0, 'position' => 0];
            $scoredCount = 0;
            foreach ($subjects as $subject) {
                $subScores = $allScores->where('student_id', $student->id)->where('subject_id', $subject->id);
                $hasScores = $subScores->isNotEmpty();
                $tot       = $hasScores ? round($subScores->sum('score'), 1) : null;
                $grade     = $hasScores ? $gradingSystem->filter(fn($g) => $tot >= $g->min_score && $tot <= $g->max_score)->first() : null;
                $row['subjects'][$subject->id] = [
                    'has_scores' => $hasScores,
                    'total'      => $tot,
                    'grade'      => $grade?->grade_letter ?? '—',
                    'is_pass'    => $grade?->is_pass_grade ?? false,
                ];
                if ($hasScores) { $row['total'] += $tot; $scoredCount++; }
            }
            $row['average'] = $scoredCount > 0 ? round($row['total'] / $scoredCount, 1) : 0;
            $matrix[$student->id] = $row;
        }

        uasort($matrix, fn($a, $b) => $b['average'] <=> $a['average']);
        $pos = 1; $prevAvg = null; $prevPos = 1;
        foreach ($matrix as &$row) {
            if ($row['average'] === $prevAvg) { $row['position'] = $prevPos; }
            else { $row['position'] = $pos; $prevPos = $pos; $prevAvg = $row['average']; }
            $pos++;
        }
        unset($row);

        $tenant = auth()->user()->tenant;

        $logoAbsPath = null;
        if (!empty($tenant->logo_path)) {
            $cleanPath = preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/'));
            $candidate = storage_path('app/public/' . $cleanPath);
            if (file_exists($candidate)) $logoAbsPath = $candidate;
        }

        $pdf = Pdf::loadView('scores.broadsheet-pdf', compact(
            'classArm', 'term', 'students', 'subjects', 'matrix', 'subjectStats', 'tenant', 'logoAbsPath'
        ))->setPaper('a4', 'landscape');

        $filename = 'Broadsheet_' . str_replace(' ', '_', $classArm->classLevel->name . '_' . $classArm->name) . '_' . str_replace(' ', '_', $term->name) . '.pdf';
        return $pdf->download($filename);
    }

    // ---------------------------------------------------------------
    // ASSESSMENT TYPES
    // ---------------------------------------------------------------
    public function assessmentTypes()
    {
        // Assessment types management is admin/full-scores only.
        // The middleware already blocks scores.entry and scores.view roles,
        // but we enforce it here too as a defense-in-depth check.
        abort_unless($this->hasFullScoreAccess(auth()->user()), 403,
            'Only administrators can manage assessment types.');

        $terms           = Term::with('session')->latest()->get();
        $assessmentTypes = AssessmentType::with('term')->latest()->get();
        $sessions        = \App\Models\AcademicSession::orderBy('name')->get();

        return view('scores.assessment-types', compact('assessmentTypes', 'terms', 'sessions'));
    }

    public function storeAssessmentType(Request $request)
    {
        abort_unless($this->hasFullScoreAccess(auth()->user()), 403);
        $validated = $request->validate([
            'term_id'           => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'name'              => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'is_exam'           => ['boolean'],
        ]);

        $currentTotal = AssessmentType::where('term_id', $validated['term_id'])->sum('weight_percentage');
        if ($currentTotal + $validated['weight_percentage'] > 100) {
            return back()->withErrors([
                'weight_percentage' => "Total weight would exceed 100%. Current: {$currentTotal}%."
            ]);
        }

        $validated['is_exam'] = $request->boolean('is_exam');
        AssessmentType::create($validated);

        return back()->with('success', 'Assessment type created.');
    }

    public function destroyAssessmentType(\App\Models\AssessmentType $at)
    {
        abort_unless($this->hasFullScoreAccess(auth()->user()), 403);
        $at->delete();
        return back()->with('success', 'Assessment type deleted.');
    }

}
