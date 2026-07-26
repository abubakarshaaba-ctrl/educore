<?php

namespace App\Http\Controllers;

use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Services\PrincipalRemarkService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportCardController extends Controller
{
    // ---------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function isAdminTier(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->canAccessExactModule('students');
    }

    /** True if this user has full reports management access (not just remarks). */
    private function hasFullReportsAccess(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->canAccessExactModule('reports');
    }

    /** Abort unless the user has full reports access. */
    private function assertFullReportsAccess(): void
    {
        abort_unless($this->hasFullReportsAccess(), 403,
            'You do not have permission to perform this action on report cards.');
    }

    /** Abort unless the user is admin-tier or is the form tutor of the given class (for remarks). */
    private function assertFormTutorOrAdmin(ClassArm $classArm): void
    {
        if ($this->isAdminTier()) return;
        abort_if(
            $classArm->form_tutor_id !== auth()->id(),
            403,
            'You can only enter remarks for your assigned form class.'
        );
    }

    /** Return class arms the current user may access for remarks. */
    private function scopedClassArms()
    {
        if ($this->isAdminTier()) {
            return ClassArm::with('classLevel')->get();
        }
        return ClassArm::with('classLevel')
            ->where('form_tutor_id', auth()->id())
            ->get();
    }

    // ---------------------------------------------------------------
    // INDEX — admin/full-reports only
    // ---------------------------------------------------------------
    public function index()
    {
        $this->assertFullReportsAccess();
        $classArms = ClassArm::with('classLevel')->get();
        $terms     = Term::with('session')->latest()->get();
        return view('reports.index', compact('classArms', 'terms'));
    }

    // ---------------------------------------------------------------
    // COMPUTE — Calculate and store term summaries (admin only)
    // ---------------------------------------------------------------
    public function compute(Request $request)
    {
        $this->assertFullReportsAccess();
        $tid = $this->tenantId();
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'term_id'      => ['required', Rule::exists('terms','id')->where('tenant_id', $tid)],
        ]);

        $classArm      = ClassArm::with('classLevel', 'formTutor')->findOrFail($request->class_arm_id);
        $term          = Term::findOrFail($request->term_id);
        $students      = Student::where('current_class_arm_id', $classArm->id)->where('status', Student::STATUS_ACTIVE)->get();
        $gradingSystem = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        $computed = 0;
        DB::transaction(function () use ($students, $term, $classArm, $gradingSystem, &$computed) {

            $termName    = strtolower($term->name);
            $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');

            // Load ALL scores for all students in this class at once
            $studentIds = $students->pluck('id');
            $allScores  = Score::whereIn('student_id', $studentIds)->where('term_id', $term->id)->get();
            $subjectIds = $allScores->pluck('subject_id')->unique();
            $subjects   = Subject::whereIn('id', $subjectIds)->orderBy('name')->get()->keyBy('id');

            // Per-subject class totals — used for highest, lowest, position, class avg
            // $subjectClassTotals[$subjectId][$studentId] = total
            $subjectClassTotals = [];
            foreach ($subjects as $subjectId => $subject) {
                foreach ($studentIds as $sid) {
                    $t = $allScores->where('student_id', $sid)->where('subject_id', $subjectId)->sum('score');
                    if ($t > 0) {
                        $subjectClassTotals[$subjectId][$sid] = round($t, 1);
                    }
                }
            }

            // Term 1 & 2 scores for 3rd-term cumulative (loaded once if needed)
            $priorTermScores = [];
            if ($isThirdTerm) {
                $allTerms = Term::where('session_id', $term->session_id)->orderBy('start_date')->get();
                foreach ($allTerms as $i => $t2) {
                    if ($t2->id === $term->id) continue;
                    $priorTermScores[$t2->id] = Score::whereIn('student_id', $studentIds)
                        ->where('term_id', $t2->id)->get();
                }
                $orderedTermIds = $allTerms->pluck('id')->values();
            }

            // First pass: compute per-student averages
            $averages = [];
            foreach ($students as $student) {
                $scores        = $allScores->where('student_id', $student->id);
                $subjectTotals = $scores->groupBy('subject_id')->map(fn($s) => round($s->sum('score'), 1));
                $subjectCount  = $subjectTotals->count();
                $grandTotal    = round($subjectTotals->sum(), 1);
                $average       = $subjectCount > 0 ? round($grandTotal / $subjectCount, 2) : 0;

                $failedCount = 0;
                foreach ($subjectTotals as $total) {
                    $grade = $gradingSystem->filter(fn($g) => $total >= $g->min_score && $total <= $g->max_score)->first();
                    if ($grade && !$grade->is_pass_grade) $failedCount++;
                }

                $averages[$student->id] = [
                    'student'          => $student,
                    'average'          => $average,
                    'grand_total'      => $grandTotal,
                    'total_score'      => $grandTotal,
                    'subjects_offered' => $subjectCount,
                    'subjects_failed'  => $failedCount,
                    'subject_totals'   => $subjectTotals,
                ];
            }

            // Class stats
            $allAverages     = collect($averages)->pluck('average');
            $classHighestAvg = $allAverages->max() ?? 0;
            $classLowestAvg  = $allAverages->min() ?? 0;

            // Rank — handle ties: same avg → same position
            $sortedAverages = collect($averages)->sortByDesc('average');
            $position = 1;
            $prevAvg  = null;
            $prevPos  = 1;
            foreach ($sortedAverages as $sid => $data) {
                if ($data['average'] === $prevAvg) {
                    $averages[$sid]['position'] = $prevPos;
                } else {
                    $averages[$sid]['position'] = $position;
                    $prevPos = $position;
                    $prevAvg = $data['average'];
                }
                $position++;
            }

            $total = count($averages);

            // Build and save summaries
            foreach ($averages as $studentId => $data) {
                $student = $data['student'];

                // Build subject_breakdown with per-subject position, class stats, grades
                $breakdown = [];
                foreach ($subjects as $subjectId => $subject) {
                    $subTotal = $data['subject_totals'][$subjectId] ?? 0;
                    if ($subTotal == 0 && !isset($data['subject_totals'][$subjectId])) continue;

                    $classTotalsForSubject = $subjectClassTotals[$subjectId] ?? [];
                    $classHighest = $classTotalsForSubject ? round(max($classTotalsForSubject), 1) : null;
                    $classLowest  = $classTotalsForSubject ? round(min($classTotalsForSubject), 1) : null;
                    $classAvg     = $classTotalsForSubject ? round(array_sum($classTotalsForSubject) / count($classTotalsForSubject), 1) : null;

                    // Subject position with tie handling
                    $subjectPos = '—';
                    if ($classTotalsForSubject) {
                        arsort($classTotalsForSubject);
                        $pos = 1; $prevT = null; $prevP = 1;
                        foreach ($classTotalsForSubject as $sid2 => $t2) {
                            if ($t2 === $prevT) {
                                if ($sid2 == $studentId) { $subjectPos = $prevP; break; }
                            } else {
                                if ($sid2 == $studentId) { $subjectPos = $pos; break; }
                                $prevP = $pos; $prevT = $t2;
                            }
                            $pos++;
                        }
                    }

                    $grade = $gradingSystem->filter(fn($g) => $subTotal >= $g->min_score && $subTotal <= $g->max_score)->first();

                    $entry = [
                        'subject_id'    => $subjectId,
                        'subject'       => $subject->name,
                        'total'         => $subTotal,
                        'grade'         => $grade?->grade_letter ?? '—',
                        'remark'        => $grade?->remark ?? '—',
                        'is_pass'       => $grade?->is_pass_grade ?? false,
                        'position'      => $subjectPos,
                        'class_highest' => $classHighest,
                        'class_lowest'  => $classLowest,
                        'class_avg'     => $classAvg,
                    ];

                    // 3rd-term: add per-term totals and cumulative average
                    if ($isThirdTerm) {
                        $termTotals = [];
                        foreach ($priorTermScores as $tId => $tScores) {
                            $termTotals[$tId] = round($tScores->where('student_id', $studentId)->where('subject_id', $subjectId)->sum('score'), 1);
                        }
                        // Current term total
                        $termTotals[$term->id] = $subTotal;

                        $annualTotal    = round(array_sum($termTotals), 1);
                        $cumulativeAvg  = round($annualTotal / max(count($termTotals), 1), 1);
                        $annualGrade    = $gradingSystem->filter(fn($g) => $cumulativeAvg >= $g->min_score && $cumulativeAvg <= $g->max_score)->first();

                        $entry['annual_total']    = $annualTotal;
                        $entry['cumulative_avg']  = $cumulativeAvg;
                        $entry['grade']           = $annualGrade?->grade_letter ?? '—';
                        $entry['remark']          = $annualGrade?->remark ?? '—';
                        $entry['is_pass']         = $annualGrade?->is_pass_grade ?? false;
                        $entry['term_totals']     = $termTotals;
                    }

                    $breakdown[] = $entry;
                }

                TermlySummary::updateOrCreate(
                    ['student_id' => $studentId, 'term_id' => $term->id, 'class_arm_id' => $classArm->id],
                    [
                        'session_id'              => $term->session_id,
                        'total_score'             => $data['total_score'],
                        'grand_total'             => $data['grand_total'],
                        'final_average'           => $data['average'],
                        'subjects_offered'        => $data['subjects_offered'],
                        'subjects_failed'         => $data['subjects_failed'],
                        'position_in_class'       => $data['position'],
                        'total_students_in_class' => $total,
                        'class_highest_avg'       => $classHighestAvg,
                        'class_lowest_avg'        => $classLowestAvg,
                        'subject_breakdown'       => $breakdown,
                    ]
                );
                $computed++;
            }
        });

        return redirect()->route('reports.preview', [
            'class_arm_id' => $classArm->id,
            'term_id'      => $term->id,
        ])->with('success', "{$computed} report cards computed.");
    }

    // ---------------------------------------------------------------
    // PREVIEW
    // ---------------------------------------------------------------
    public function preview(Request $request)
    {
        $tid = $this->tenantId();
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'term_id'      => ['required', Rule::exists('terms','id')->where('tenant_id', $tid)],
        ]);

        $this->assertFullReportsAccess();
        $classArm = ClassArm::with('classLevel', 'formTutor')->findOrFail($request->class_arm_id);
        $term     = Term::with('session')->findOrFail($request->term_id);
        $session  = $term->session;

        $summaries = TermlySummary::where('class_arm_id', $classArm->id)
                                  ->where('term_id', $term->id)
                                  ->with('student')
                                  ->orderBy('position_in_class')
                                  ->get();

        $assessmentTypes = AssessmentType::where('term_id', $term->id)->orderBy('is_exam')->orderBy('name')->get();
        $studentIds      = $summaries->pluck('student_id');
        $allScores       = Score::whereIn('student_id', $studentIds)->where('term_id', $term->id)->get();
        $subjects        = Subject::whereIn('id', $allScores->pluck('subject_id')->unique())->orderBy('name')->get()->keyBy('id');
        $gradingSys      = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        // Per-subject: highest and lowest totals in class
        $subjectClassStats = [];
        foreach ($subjects as $subjectId => $subject) {
            $subjectTotals = [];
            foreach ($studentIds as $sid) {
                $t = $allScores->where('student_id', $sid)->where('subject_id', $subjectId)->sum('score');
                if ($t > 0) $subjectTotals[] = $t;
            }
            $subjectClassStats[$subjectId] = [
                'highest' => count($subjectTotals) ? round(max($subjectTotals), 1) : '—',
                'lowest'  => count($subjectTotals) ? round(min($subjectTotals), 1) : '—',
            ];
        }

        $subjectScores = [];
        foreach ($studentIds as $studentId) {
            foreach ($subjects as $subjectId => $subject) {
                $subScores = $allScores->where('student_id', $studentId)->where('subject_id', $subjectId);
                $total     = round($subScores->sum('score'), 1);
                $grade     = $gradingSys->filter(fn($g) => $total >= $g->min_score && $total <= $g->max_score)->first();
                $scoresKeyed = [];
                foreach ($subScores as $s) {
                    $scoresKeyed[$s->assessment_type_id] = $s->score;
                }
                $subjectScores[$studentId][$subjectId] = [
                    'subject_name' => $subject->name,
                    'scores'       => $scoresKeyed,
                    'has_scores'   => $subScores->isNotEmpty(),
                    'total'        => $total,
                    'grade'        => $grade?->grade_letter ?? '—',
                    'remark'       => $grade?->remark ?? '—',
                    'is_pass'      => $grade?->is_pass_grade ?? false,
                ];
            }
        }

        return view('reports.preview', compact(
            'classArm', 'term', 'session', 'summaries',
            'assessmentTypes', 'subjectScores', 'subjectClassStats'
        ));
    }

    // ---------------------------------------------------------------
    // PDF — Single student
    // ---------------------------------------------------------------
    public function pdf(Request $request, Student $student)
    {
        $this->assertFullReportsAccess();
        abort_unless($student->tenant_id === $this->tenantId(), 403);
        $term     = Term::with('session')->findOrFail($request->term_id);
        abort_unless($term->tenant_id === $this->tenantId(), 403);
        $classArm = ClassArm::with('classLevel', 'formTutor')->findOrFail($student->current_class_arm_id);
        $session  = $term->session;
        $tenant   = auth()->user()->tenant;

        $summary = TermlySummary::where('student_id', $student->id)->where('term_id', $term->id)->firstOrFail();

        $termName    = strtolower($term->name);
        $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');
        $orientation = $isThirdTerm ? 'landscape' : 'portrait';

        $assessmentTypes = AssessmentType::where('term_id', $term->id)->orderBy('is_exam')->orderBy('name')->get();
        $rawScores       = Score::where('student_id', $student->id)->where('term_id', $term->id)->get();
        $subjects        = Subject::whereIn('id', $rawScores->pluck('subject_id')->unique())->orderBy('name')->get();
        $gradingSystem   = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        // Class scores for highest/lowest per subject
        $classmateIds = Student::where('current_class_arm_id', $classArm->id)->where('status', Student::STATUS_ACTIVE)->pluck('id');
        $classScores  = Score::whereIn('student_id', $classmateIds)->where('term_id', $term->id)->get();

        $subjectRows = [];
        foreach ($subjects as $subject) {
            $subScores = $rawScores->where('subject_id', $subject->id);
            $total     = round($subScores->sum('score'), 1);
            $grade     = $gradingSystem->filter(fn($g) => $total >= $g->min_score && $total <= $g->max_score)->first();

            $scoresKeyed = [];
            foreach ($subScores as $s) {
                $scoresKeyed[$s->assessment_type_id] = $s->score;
            }

            // Class highest/lowest for this subject
            $classTotals = [];
            foreach ($classmateIds as $cid) {
                $t = $classScores->where('student_id', $cid)->where('subject_id', $subject->id)->sum('score');
                if ($t > 0) $classTotals[] = $t;
            }

            // Class average and position for this subject
            $classAvgSubject = count($classTotals) > 0 ? round(array_sum($classTotals) / count($classTotals), 2) : null;
            // Student position in class for this subject
            $subjectPosition = '—';
            if (count($classTotals) > 0) {
                arsort($classTotals);
                $rank = array_search($total, array_values($classTotals));
                $subjectPosition = $rank !== false ? $rank + 1 : '—';
            }

            $row = [
                'subject_name'   => $subject->name,
                'scores'         => $scoresKeyed,
                'total'          => $total,
                'grade'          => $grade?->grade_letter ?? '—',
                'remark'         => $grade?->remark ?? '—',
                'is_pass'        => $grade?->is_pass_grade ?? false,
                'class_highest'  => count($classTotals) ? round(max($classTotals), 1) : '—',
                'class_lowest'   => count($classTotals) ? round(min($classTotals), 1) : '—',
                'class_avg'      => $classAvgSubject ? number_format($classAvgSubject, 2) : '—',
                'class_position' => $subjectPosition,
                'class_count'    => count($classmateIds),
            ];

            if ($isThirdTerm) {
                $allTerms = Term::where('session_id', $term->session_id)->orderBy('start_date')->get();
                foreach ($allTerms as $i => $t2) {
                    $tScores = Score::where('student_id', $student->id)->where('subject_id', $subject->id)->where('term_id', $t2->id)->get();
                    $row['term' . ($i + 1) . '_avg'] = round($tScores->sum('score'), 1);
                }
                $annualTotal = ($row['term1_avg'] ?? 0) + ($row['term2_avg'] ?? 0) + ($row['term3_avg'] ?? 0);
                $row['annual_total']   = round($annualTotal, 1);
                $row['cumulative_avg'] = round($annualTotal / 3, 1);
                // Grade and remark based on annual average, not just 3rd-term total
                $annualGrade   = $gradingSystem->filter(fn($g) => $row['cumulative_avg'] >= $g->min_score && $row['cumulative_avg'] <= $g->max_score)->first();
                $row['grade']   = $annualGrade?->grade_letter ?? '—';
                $row['remark']  = $annualGrade?->remark       ?? '—';
                $row['is_pass'] = $annualGrade?->is_pass_grade ?? false;
            }

            $subjectRows[] = $row;
        }

        $psychomotorSkills = SkillDefinition::where('category', 'psychomotor')->get();
        $affectiveSkills   = SkillDefinition::where('category', 'affective')->get();
        $skillRatings      = StudentSkillRating::where('student_id', $student->id)->where('term_id', $term->id)->get();

        // Auto-generate principal remark if empty
        if (empty($summary->principal_remark)) {
            $remark = PrincipalRemarkService::generate(
                average:        $summary->final_average,
                position:       $summary->position_in_class,
                totalStudents:  $summary->total_students_in_class,
                subjectsFailed: $summary->subjects_failed,
                studentName:    $student->first_name,
                rotationSeed:   $student->id
            );
            $summary->update(['principal_remark' => $remark]);
            $summary->refresh();
        }

        // Attendance summary
        $attendanceSummary = [];
        if (class_exists('\App\Models\AttendanceRecord')) {
            $present = \App\Models\AttendanceRecord::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->whereIn('status', ['present', 'late'])->count();
            $absent  = \App\Models\AttendanceRecord::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->where('status', 'absent')->count();
            $daysOpen = \App\Models\AttendanceRecord::where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->distinct('attendance_date')->count('attendance_date');
            $attendanceSummary = [
                'days_open'    => $daysOpen ?: '—',
                'days_present' => $present,
                'days_absent'  => $absent,
                'rate'         => $daysOpen > 0 ? round(($present / $daysOpen) * 100) : '—',
            ];
        }

        // Class average summary (across all students)
        $classSummaries = \App\Models\TermlySummary::where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)->get();
        $summaries_class_avg = $classSummaries->count() > 0
            ? number_format($classSummaries->avg('final_average'), 2) : null;

        $pdf = Pdf::loadView('reports.pdf', compact(
            'student', 'classArm', 'term', 'session', 'tenant',
            'summary', 'isThirdTerm', 'assessmentTypes', 'subjectRows',
            'gradingSystem', 'psychomotorSkills', 'affectiveSkills', 'skillRatings',
            'attendanceSummary', 'summaries_class_avg'
        ))->setPaper('a4', $orientation);

        $filename = 'ReportCard_' . str_replace(' ', '_', $student->full_name) . '_' . str_replace(' ', '_', $term->name) . '.pdf';
        return $pdf->download($filename);
    }

    // ---------------------------------------------------------------
    // STUDENT SELF-SERVICE PDF — accessible from the student portal
    // ---------------------------------------------------------------
    public function studentPdf(Request $request)
    {
        /** @var \App\Models\User $user */
        $user    = auth()->user();
        $student = $user->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $term = Term::with('session')->findOrFail($request->term_id);
        abort_unless($term->tenant_id === $student->tenant_id, 403);

        $summary = TermlySummary::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        $classArm = ClassArm::with('classLevel', 'formTutor')->findOrFail($student->current_class_arm_id);
        $session  = $term->session;
        $tenant   = $user->tenant;

        $termName    = strtolower($term->name);
        $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');
        $orientation = $isThirdTerm ? 'landscape' : 'portrait';

        $assessmentTypes = AssessmentType::where('term_id', $term->id)->orderBy('is_exam')->orderBy('name')->get();
        $rawScores       = Score::where('student_id', $student->id)->where('term_id', $term->id)->get();
        $subjects        = Subject::whereIn('id', $rawScores->pluck('subject_id')->unique())->orderBy('name')->get();
        $gradingSystem   = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        $classmateIds = Student::where('current_class_arm_id', $classArm->id)->where('status', Student::STATUS_ACTIVE)->pluck('id');
        $classScores  = Score::whereIn('student_id', $classmateIds)->where('term_id', $term->id)->get();

        $subjectRows = [];
        foreach ($subjects as $subject) {
            $subScores   = $rawScores->where('subject_id', $subject->id);
            $total       = round($subScores->sum('score'), 1);
            $grade       = $gradingSystem->filter(fn($g) => $total >= $g->min_score && $total <= $g->max_score)->first();
            $scoresKeyed = [];
            foreach ($subScores as $s) {
                $scoresKeyed[$s->assessment_type_id] = $s->score;
            }
            $classTotals = [];
            foreach ($classmateIds as $cid) {
                $t = $classScores->where('student_id', $cid)->where('subject_id', $subject->id)->sum('score');
                if ($t > 0) $classTotals[] = $t;
            }
            $subjectRows[] = [
                'subject'       => $subject,
                'scores_keyed'  => $scoresKeyed,
                'total'         => $total,
                'grade'         => $grade,
                'class_highest' => $classTotals ? max($classTotals) : null,
                'class_lowest'  => $classTotals ? min($classTotals) : null,
            ];
        }

        $psychomotorSkills = \App\Models\SkillDefinition::psychomotor()->where('tenant_id', $tenant->id)->get();
        $affectiveSkills   = \App\Models\SkillDefinition::affective()->where('tenant_id', $tenant->id)->get();
        $skillRatings      = StudentSkillRating::where('student_id', $student->id)->where('term_id', $term->id)->get();

        $pdf      = Pdf::loadView('reports.pdf', compact(
            'student', 'classArm', 'term', 'session', 'tenant',
            'summary', 'isThirdTerm', 'assessmentTypes', 'subjectRows',
            'gradingSystem', 'psychomotorSkills', 'affectiveSkills', 'skillRatings'
        ))->setPaper('a4', $orientation);

        $filename = 'ReportCard_' . str_replace(' ', '_', $student->full_name) . '_' . str_replace(' ', '_', $term->name) . '.pdf';
        return $pdf->download($filename);
    }

    // ---------------------------------------------------------------
    // BULK PDF
    // ---------------------------------------------------------------
    public function pdfClass(Request $request)
    {
        $tid = $this->tenantId();
        $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'term_id'      => ['required', Rule::exists('terms','id')->where('tenant_id', $tid)],
        ]);

        $this->assertFullReportsAccess();
        $classArm      = ClassArm::with('classLevel', 'formTutor')->findOrFail($request->class_arm_id);
        $term          = Term::with('session')->findOrFail($request->term_id);
        $tenant        = auth()->user()->tenant;
        $session       = $term->session;
        $termName      = strtolower($term->name);
        $isThirdTerm   = str_contains($termName, '3rd') || str_contains($termName, 'third');
        $orientation   = $isThirdTerm ? 'landscape' : 'portrait';

        $summaries       = TermlySummary::where('class_arm_id', $classArm->id)->where('term_id', $term->id)->with('student')->orderBy('position_in_class')->get();
        $assessmentTypes = AssessmentType::where('term_id', $term->id)->orderBy('is_exam')->orderBy('name')->get();
        $gradingSystem   = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();
        $psychomotorSkills = SkillDefinition::where('category', 'psychomotor')->get();
        $affectiveSkills   = SkillDefinition::where('category', 'affective')->get();
        $classmateIds    = $summaries->pluck('student_id');
        $classScores     = Score::whereIn('student_id', $classmateIds)->where('term_id', $term->id)->get();

        $studentData = [];
        foreach ($summaries as $summary) {
            $student   = $summary->student;
            $rawScores = Score::where('student_id', $student->id)->where('term_id', $term->id)->get();
            $subjects  = Subject::whereIn('id', $rawScores->pluck('subject_id')->unique())->orderBy('name')->get();

            if (empty($summary->principal_remark)) {
                $remark = PrincipalRemarkService::generate(
                    average: $summary->final_average, position: $summary->position_in_class,
                    totalStudents: $summary->total_students_in_class, subjectsFailed: $summary->subjects_failed,
                    studentName: $student->first_name, rotationSeed: $student->id
                );
                $summary->update(['principal_remark' => $remark]);
                $summary->refresh();
            }

            $subjectRows = [];
            foreach ($subjects as $subject) {
                $subScores = $rawScores->where('subject_id', $subject->id);
                $total     = round($subScores->sum('score'), 1);
                $grade     = $gradingSystem->filter(fn($g) => $total >= $g->min_score && $total <= $g->max_score)->first();
                $scoresKeyed = [];
                foreach ($subScores as $s) { $scoresKeyed[$s->assessment_type_id] = $s->score; }

                $classTotals = [];
                foreach ($classmateIds as $cid) {
                    $t = $classScores->where('student_id', $cid)->where('subject_id', $subject->id)->sum('score');
                    if ($t > 0) $classTotals[] = $t;
                }

                $subjectRows[] = [
                    'subject_id'    => $subject->id,
                    'subject_name'  => $subject->name,
                    'scores'        => $scoresKeyed,
                    'total'         => $total,
                    'grade'         => $grade?->grade_letter ?? '—',
                    'remark'        => $grade?->remark ?? '—',
                    'is_pass'       => $grade?->is_pass_grade ?? false,
                    'class_highest' => count($classTotals) ? round(max($classTotals), 1) : '—',
                    'class_lowest'  => count($classTotals) ? round(min($classTotals), 1) : '—',
                    'class_position' => '—',
                ];
            }

            // 3rd-term cumulative: add per-term totals, annual total, and re-derive grade from average
            if ($isThirdTerm) {
                $allTerms      = Term::where('session_id', $term->session_id)->orderBy('start_date')->get();
                $allTermScores = Score::where('student_id', $student->id)
                                      ->whereIn('term_id', $allTerms->pluck('id'))->get();
                foreach ($subjectRows as &$row) {
                    foreach ($allTerms as $i => $t2) {
                        $row['term' . ($i + 1) . '_avg'] = round(
                            $allTermScores->where('subject_id', $row['subject_id'])->where('term_id', $t2->id)->sum('score'), 1
                        );
                    }
                    $annualTotal           = ($row['term1_avg'] ?? 0) + ($row['term2_avg'] ?? 0) + ($row['term3_avg'] ?? 0);
                    $row['annual_total']   = round($annualTotal, 1);
                    $row['cumulative_avg'] = round($annualTotal / 3, 1);
                    $annualGrade           = $gradingSystem->filter(fn($g) => $row['cumulative_avg'] >= $g->min_score && $row['cumulative_avg'] <= $g->max_score)->first();
                    $row['grade']          = $annualGrade?->grade_letter ?? '—';
                    $row['remark']         = $annualGrade?->remark       ?? '—';
                    $row['is_pass']        = $annualGrade?->is_pass_grade ?? false;
                }
                unset($row);
            }

            $skillRatings = StudentSkillRating::where('student_id', $student->id)->where('term_id', $term->id)->get();
            $studentData[] = compact('student', 'summary', 'subjectRows', 'skillRatings');
        }

        $pdf = Pdf::loadView('reports.pdf-class', compact(
            'classArm', 'term', 'session', 'tenant',
            'isThirdTerm', 'assessmentTypes', 'gradingSystem',
            'psychomotorSkills', 'affectiveSkills', 'studentData'
        ))->setPaper('a4', $orientation);

        $filename = 'ReportCards_' . $classArm->classLevel->name . '_' . $classArm->name . '_' . $term->name . '.pdf';
        return $pdf->download($filename);
    }

    // ---------------------------------------------------------------
    // SAVE REMARKS
    // ---------------------------------------------------------------
    public function saveRemark(Request $request, TermlySummary $summary)
    {
        $request->validate([
            'field' => ['required', 'in:form_tutor_remark,principal_remark'],
            'class_arm_id' => ['nullable', 'exists:class_arms,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'form_tutor_remark' => ['nullable', 'string', 'max:2000'],
            'principal_remark' => ['nullable', 'string', 'max:2000'],
        ]);

        // Derive the class arm from the summary being edited, not the request,
        // so a form teacher cannot spoof authorization by passing a different class_arm_id.
        $classArm = ClassArm::findOrFail($summary->class_arm_id);
        $this->assertFormTutorOrAdmin($classArm);

        $field = $request->input('field');

        // Form teachers may only edit form_tutor_remark. principal_remark is admin-only.
        if (!$this->hasFullReportsAccess() && $field === 'principal_remark') {
            abort(403, 'Only administrators can edit the principal remark.');
        }

        $summary->update([
            $field => $request->input($field),
        ]);

        return redirect()->route('reports.remarks', [
            'class_arm_id' => $request->integer('class_arm_id') ?: $summary->class_arm_id,
            'term_id'      => $request->integer('term_id')      ?: $summary->term_id,
        ])->with('success', 'Remark saved.');
    }

    // ---------------------------------------------------------------
    // REMARKS PAGE
    // ---------------------------------------------------------------
    public function remarks(Request $request)
    {
        $classArms = $this->scopedClassArms();
        $terms     = Term::with('session')->latest()->get();

        if (!$request->filled('class_arm_id') || !$request->filled('term_id')) {
            return view('reports.remarks', compact('classArms', 'terms'));
        }

        $classArm  = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $this->assertFormTutorOrAdmin($classArm);
        $term      = Term::with('session')->findOrFail($request->term_id);
        $summaries = TermlySummary::where('class_arm_id', $classArm->id)->where('term_id', $term->id)
                                  ->with('student')->orderBy('position_in_class')->get();

        return view('reports.remarks', compact(
            'classArms', 'terms', 'classArm', 'term', 'summaries'
        ));
    }

    // ---------------------------------------------------------------
    // BULK AUTO-GENERATE PRINCIPAL REMARKS
    // ---------------------------------------------------------------
    public function bulkRemarks(Request $request)
    {
        // Bulk principal-remark generation is an admin-only operation.
        $this->assertFullReportsAccess();
        $request->validate(['class_arm_id' => ['required'], 'term_id' => ['required']]);

        $classArm  = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term      = Term::findOrFail($request->term_id);
        $summaries = TermlySummary::where('class_arm_id', $classArm->id)->where('term_id', $term->id)->with('student')->get();

        $count = 0;
        foreach ($summaries as $summary) {
            $remark = PrincipalRemarkService::generate(
                average: $summary->final_average, position: $summary->position_in_class,
                totalStudents: $summary->total_students_in_class, subjectsFailed: $summary->subjects_failed,
                studentName: $summary->student->first_name, rotationSeed: $summary->student_id
            );
            $summary->update(['principal_remark' => $remark]);
            $count++;
        }

        return redirect()->route('reports.remarks', [
            'class_arm_id' => $classArm->id,
            'term_id' => $term->id,
        ])->with('success', "Principal remarks auto-generated for {$count} students.");
    }

    // ── Publication Management ─────────────────────────────────────────
    public function publish(\Illuminate\Http\Request $request)
    {
        abort_unless($this->isAdminTier(), 403, 'Only administrators can publish report cards.');

        $tid = $this->tenantId();
        $request->validate([
            'term_id'         => ['required', Rule::exists('terms','id')->where('tenant_id', $tid)],
            'class_arm_id'    => ['required_without:class_arm_ids', Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'class_arm_ids'   => ['required_without:class_arm_id', 'array'],
            'class_arm_ids.*' => [Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'note'            => ['nullable', 'string'],
        ]);

        $termId   = $request->term_id;
        $armIds   = $request->class_arm_ids ?? [$request->class_arm_id];
        $published = 0;
        $term = \App\Models\Term::find($termId);
        $schoolName = auth()->user()->tenant?->name;

        foreach ($armIds as $armId) {
            $summaries = \App\Models\TermlySummary::where('class_arm_id', $armId)
                ->where('term_id', $termId)
                ->with('student.guardians')
                ->get();
            if ($summaries->isEmpty()) continue;

            \App\Models\ReportCardPublication::updateOrCreate(
                ['class_arm_id' => $armId, 'term_id' => $termId],
                [
                    'status'       => 'published',
                    'published_at' => now(),
                    'published_by' => auth()->id(),
                    'note'         => $request->note,
                    'archived_at'  => null,
                ]
            );
            $published++;

            $notifier = app(\App\Services\GuardianNotifier::class);
            foreach ($summaries as $summary) {
                $student = $summary->student;
                if (!$student) continue;

                $guardian = $student->guardians->firstWhere('pivot.is_primary_contact', true)
                    ?? $student->guardians->first();

                try {
                    $notifier->send(
                        $guardian,
                        'Results published — ' . $student->full_name,
                        [
                            ($term?->name ?? 'Term') . " results for {$student->full_name} are now available.",
                            'Sign in to the parent portal to view the full report card.',
                        ],
                        smsBody: "Dear Parent, {$student->full_name}'s " . ($term?->name ?? 'term') . ' results are now available on the EduCore parent portal.',
                        actionLabel: 'View Results',
                        actionUrl: route('login'),
                        schoolName: $schoolName,
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Results-published notification failed for student {$student->id}: " . $e->getMessage());
                }
            }
        }

        $msg = count($armIds) === 1
            ? 'Report cards published. Parents can now view results.'
            : "{$published} class(es) published. Parents can now view results.";

        return redirect()->route('reports.publications', ['term_id' => $termId])
                         ->with('success', $msg);
    }

    public function unpublish(\Illuminate\Http\Request $request)
    {
        abort_unless($this->isAdminTier(), 403, 'Only administrators can unpublish report cards.');

        $tid = $this->tenantId();
        $request->validate([
            'term_id'         => ['required', Rule::exists('terms','id')->where('tenant_id', $tid)],
            'class_arm_id'    => ['required_without:class_arm_ids', Rule::exists('class_arms','id')->where('tenant_id', $tid)],
            'class_arm_ids'   => ['required_without:class_arm_id', 'array'],
            'class_arm_ids.*' => [Rule::exists('class_arms','id')->where('tenant_id', $tid)],
        ]);

        $termId = $request->term_id;
        $armIds = $request->class_arm_ids ?? [$request->class_arm_id];

        \App\Models\ReportCardPublication::whereIn('class_arm_id', $armIds)
            ->where('term_id', $termId)
            ->update(['status' => 'draft', 'archived_at' => now()]);

        $msg = count($armIds) === 1
            ? 'Report cards unpublished (set back to draft).'
            : count($armIds) . ' class(es) unpublished.';

        return redirect()->route('reports.publications', ['term_id' => $termId])
                         ->with('success', $msg);
    }

    public function publicationStatus()
    {
        $this->assertFullReportsAccess();
        $terms     = \App\Models\Term::with('session')->latest()->get();
        $classArms = ClassArm::with('classLevel')->get();
        $pubs      = \App\Models\ReportCardPublication::with(['classArm.classLevel','publishedBy'])
                        ->get()->keyBy(fn($p) => $p->class_arm_id . '_' . $p->term_id);

        // Computed counts per arm/term
        $computed  = \App\Models\TermlySummary::selectRaw('class_arm_id, term_id, COUNT(*) as count')
                        ->groupBy('class_arm_id','term_id')
                        ->get()->keyBy(fn($r) => $r->class_arm_id . '_' . $r->term_id);

        return view('reports.publications', compact('terms', 'classArms', 'pubs', 'computed'));
    }

}
