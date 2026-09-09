<?php

namespace App\Services;

use App\Models\AssessmentType;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TermlySummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ReportCardDocumentService
{
    public function download(int $tenantId, int $summaryId): Response
    {
        $summary = TermlySummary::where('tenant_id', $tenantId)
            ->whereKey($summaryId)
            ->firstOrFail();
        $student = Student::where('tenant_id', $tenantId)
            ->whereKey($summary->student_id)
            ->firstOrFail();
        $classArm = ClassArm::where('tenant_id', $tenantId)
            ->with(['classLevel', 'formTutor'])
            ->whereKey($summary->class_arm_id)
            ->firstOrFail();
        $term = Term::where('tenant_id', $tenantId)
            ->with('session')
            ->whereKey($summary->term_id)
            ->firstOrFail();
        $tenant = Tenant::findOrFail($tenantId);
        $session = $term->session;

        $isThirdTerm = $this->isThirdTerm($term);
        $assessmentTypes = $this->assessmentTypes($tenantId, $term->id);
        $rawScores = $this->studentScores($tenantId, $student->id, $term->id);
        $gradingSystem = $this->gradingSystem($tenantId, $classArm->class_level_id);
        $subjectRows = $this->subjectRows(
            $tenantId,
            $student,
            $classArm,
            $term,
            $summary,
            $assessmentTypes,
            $rawScores,
            $gradingSystem,
            $isThirdTerm,
        );

        $psychomotorSkills = $this->skills($tenantId, 'psychomotor');
        $affectiveSkills = $this->skills($tenantId, 'affective');
        $skillRatings = $this->skillRatings($tenantId, $student->id, $term->id);
        $attendanceSummary = $this->attendanceSummary($tenantId, $student->id, $classArm->id, $term->id);
        $summariesClassAvg = $this->classAverage($tenantId, $classArm->id, $term->id);

        // Preserve the legacy generated principal remark visually, but never
        // persist data as a side effect of a read/download operation.
        if (empty($summary->principal_remark)) {
            $summary->setAttribute('principal_remark', PrincipalRemarkService::generate(
                average: $summary->final_average,
                position: $summary->position_in_class,
                totalStudents: $summary->total_students_in_class,
                subjectsFailed: $summary->subjects_failed,
                studentName: $student->first_name,
                rotationSeed: $student->id,
            ));
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'student' => $student,
            'classArm' => $classArm,
            'term' => $term,
            'session' => $session,
            'tenant' => $tenant,
            'summary' => $summary,
            'isThirdTerm' => $isThirdTerm,
            'assessmentTypes' => $assessmentTypes,
            'subjectRows' => $subjectRows,
            'gradingSystem' => $gradingSystem,
            'psychomotorSkills' => $psychomotorSkills,
            'affectiveSkills' => $affectiveSkills,
            'skillRatings' => $skillRatings,
            'attendanceSummary' => $attendanceSummary,
            'summaries_class_avg' => $summariesClassAvg,
        ])->setPaper('a4', $isThirdTerm ? 'landscape' : 'portrait');

        return $pdf->download($this->filename($student, $term));
    }

    private function assessmentTypes(int $tenantId, int $termId): Collection
    {
        if (!Schema::hasTable('assessment_types')) {
            return collect();
        }

        return AssessmentType::where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->orderBy('is_exam')
            ->orderBy('name')
            ->get();
    }

    private function studentScores(int $tenantId, int $studentId, int $termId): Collection
    {
        if (!Schema::hasTable('scores')) {
            return collect();
        }

        return Score::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();
    }

    private function gradingSystem(int $tenantId, int $classLevelId): Collection
    {
        if (!Schema::hasTable('grading_systems')) {
            return collect();
        }

        return GradingSystem::where('tenant_id', $tenantId)
            ->where('class_level_id', $classLevelId)
            ->get();
    }

    private function subjectRows(
        int $tenantId,
        Student $student,
        ClassArm $classArm,
        Term $term,
        TermlySummary $summary,
        Collection $assessmentTypes,
        Collection $rawScores,
        Collection $gradingSystem,
        bool $isThirdTerm,
    ): array {
        $computed = collect($summary->subject_breakdown ?? [])
            ->filter(fn ($row): bool => is_array($row) && isset($row['subject_id']))
            ->keyBy(fn (array $row): int => (int) $row['subject_id']);
        $subjectIds = $rawScores->pluck('subject_id')
            ->merge($computed->keys())
            ->map(fn ($value): int => (int) $value)
            ->filter()
            ->unique()
            ->values();
        if ($subjectIds->isEmpty()) {
            return [];
        }

        $subjects = Schema::hasTable('subjects')
            ? Subject::where('tenant_id', $tenantId)
                ->whereIn('id', $subjectIds)
                ->orderBy('name')
                ->get()
                ->keyBy('id')
            : collect();

        $classmateIds = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->pluck('student_id');
        $classScores = Schema::hasTable('scores') && $classmateIds->isNotEmpty()
            ? Score::where('tenant_id', $tenantId)
                ->whereIn('student_id', $classmateIds)
                ->where('term_id', $term->id)
                ->whereIn('subject_id', $subjectIds)
                ->get()
            : collect();

        $allTerms = collect();
        $allTermScores = collect();
        if ($isThirdTerm) {
            $allTerms = Term::where('tenant_id', $tenantId)
                ->where('session_id', $term->session_id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();
            if (Schema::hasTable('scores') && $allTerms->isNotEmpty()) {
                $allTermScores = Score::where('tenant_id', $tenantId)
                    ->where('student_id', $student->id)
                    ->whereIn('term_id', $allTerms->pluck('id'))
                    ->whereIn('subject_id', $subjectIds)
                    ->get();
            }
        }

        $rows = [];
        foreach ($subjectIds as $subjectId) {
            $subject = $subjects->get($subjectId);
            $subjectScores = $rawScores->where('subject_id', $subjectId);
            $computedRow = $computed->get($subjectId, []);
            $scoresKeyed = [];
            foreach ($subjectScores as $score) {
                $scoresKeyed[$score->assessment_type_id] = $score->score;
            }

            $total = (float) ($computedRow['total'] ?? round($subjectScores->sum('score'), 1));
            $grade = $gradingSystem->first(
                fn ($item) => $total >= $item->min_score && $total <= $item->max_score
            );
            $classTotals = [];
            foreach ($classmateIds as $classmateId) {
                $classTotal = $classScores
                    ->where('student_id', $classmateId)
                    ->where('subject_id', $subjectId)
                    ->sum('score');
                if ($classTotal > 0) {
                    $classTotals[(int) $classmateId] = round($classTotal, 1);
                }
            }

            $row = [
                'subject_id' => $subjectId,
                'subject_name' => $subject?->name ?? ($computedRow['subject'] ?? 'Subject'),
                'scores' => $scoresKeyed,
                'total' => $total,
                'grade' => $computedRow['grade'] ?? $grade?->grade_letter ?? '—',
                'remark' => $computedRow['remark'] ?? $grade?->remark ?? '—',
                'is_pass' => (bool) ($computedRow['is_pass'] ?? $grade?->is_pass_grade ?? false),
                'class_highest' => $computedRow['class_highest'] ?? ($classTotals ? max($classTotals) : '—'),
                'class_lowest' => $computedRow['class_lowest'] ?? ($classTotals ? min($classTotals) : '—'),
                'class_avg' => $computedRow['class_avg'] ?? ($classTotals ? round(array_sum($classTotals) / count($classTotals), 2) : '—'),
                'class_position' => $computedRow['position'] ?? $this->subjectPosition($classTotals, $student->id),
                'class_count' => $classmateIds->count(),
            ];

            if ($isThirdTerm) {
                $termTotals = is_array($computedRow['term_totals'] ?? null)
                    ? $computedRow['term_totals']
                    : [];
                foreach ($allTerms as $index => $sessionTerm) {
                    $row['term'.($index + 1).'_avg'] = array_key_exists($sessionTerm->id, $termTotals)
                        ? $termTotals[$sessionTerm->id]
                        : round(
                            $allTermScores->where('term_id', $sessionTerm->id)
                                ->where('subject_id', $subjectId)
                                ->sum('score'),
                            1,
                        );
                }
                $termValues = $allTerms->keys()->map(
                    fn ($index) => (float) ($row['term'.($index + 1).'_avg'] ?? 0)
                );
                $row['annual_total'] = $computedRow['annual_total'] ?? round($termValues->sum(), 1);
                $row['cumulative_avg'] = $computedRow['cumulative_avg']
                    ?? round($row['annual_total'] / max($allTerms->count(), 1), 1);

                $annualGrade = $gradingSystem->first(
                    fn ($item) => $row['cumulative_avg'] >= $item->min_score
                        && $row['cumulative_avg'] <= $item->max_score
                );
                if (!isset($computedRow['grade'])) {
                    $row['grade'] = $annualGrade?->grade_letter ?? '—';
                    $row['remark'] = $annualGrade?->remark ?? '—';
                    $row['is_pass'] = (bool) ($annualGrade?->is_pass_grade ?? false);
                }
            }

            $rows[] = $row;
        }

        usort($rows, fn (array $a, array $b): int => strcasecmp($a['subject_name'], $b['subject_name']));

        return $rows;
    }

    private function subjectPosition(array $classTotals, int $studentId): int|string
    {
        if (!$classTotals || !array_key_exists($studentId, $classTotals)) {
            return '—';
        }

        arsort($classTotals);
        $position = 1;
        $previousTotal = null;
        $previousPosition = 1;
        foreach ($classTotals as $candidateStudentId => $candidateTotal) {
            $candidatePosition = $candidateTotal === $previousTotal ? $previousPosition : $position;
            if ((int) $candidateStudentId === $studentId) {
                return $candidatePosition;
            }
            if ($candidateTotal !== $previousTotal) {
                $previousTotal = $candidateTotal;
                $previousPosition = $position;
            }
            $position++;
        }

        return '—';
    }

    private function skills(int $tenantId, string $category): Collection
    {
        if (!Schema::hasTable('skill_definitions')) {
            return collect();
        }

        return SkillDefinition::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
    }

    private function skillRatings(int $tenantId, int $studentId, int $termId): Collection
    {
        if (!Schema::hasTable('student_skill_ratings')) {
            return collect();
        }

        return StudentSkillRating::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();
    }

    private function attendanceSummary(int $tenantId, int $studentId, int $classArmId, int $termId): array
    {
        if (!Schema::hasTable('attendance_records')) {
            return [];
        }

        $present = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->whereIn('status', ['present', 'late'])
            ->count();
        $absent = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'absent')
            ->count();
        $daysOpen = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->distinct('attendance_date')
            ->count('attendance_date');

        return [
            'days_open' => $daysOpen ?: '—',
            'days_present' => $present,
            'days_absent' => $absent,
            'rate' => $daysOpen > 0 ? round(($present / $daysOpen) * 100) : '—',
        ];
    }

    private function classAverage(int $tenantId, int $classArmId, int $termId): ?string
    {
        $average = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->avg('final_average');

        return $average === null ? null : number_format((float) $average, 2);
    }

    private function isThirdTerm(Term $term): bool
    {
        $name = strtolower($term->name);

        return str_contains($name, '3rd') || str_contains($name, 'third');
    }

    private function filename(Student $student, Term $term): string
    {
        $studentName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $student->full_name) ?: 'Student';
        $termName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $term->name) ?: 'Term';

        return "ReportCard_{$studentName}_{$termName}.pdf";
    }
}
