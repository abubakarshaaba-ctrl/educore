<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\SkillDefinition;
use App\Models\Student;
use App\Models\StudentSkillRating;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\PrincipalRemarkService;
use App\Services\ReportCardComputationService;
use App\Services\ReportCardPublicationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileReportsController extends Controller
{
    private const ADMIN_ROLES = [
        'admin', 'principal', 'head', 'head_teacher',
        'vice_principal', 'academic_administrator',
    ];

    public function __construct(
        private readonly ReportCardComputationService $computation,
        private readonly ReportCardPublicationService $publication,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'class_arm_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);

        $classArms = ClassArm::where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $classArm = null;
        $term = null;
        if (isset($data['class_arm_id'])) {
            $classArm = ClassArm::where('tenant_id', $tenantId)
                ->with('classLevel:id,name')
                ->whereKey((int) $data['class_arm_id'])
                ->firstOrFail();
        }
        if (isset($data['term_id'])) {
            $term = Term::where('tenant_id', $tenantId)
                ->with('session:id,name')
                ->whereKey((int) $data['term_id'])
                ->firstOrFail();
        }

        $summaries = collect();
        $publication = null;
        $activeStudents = null;
        if ($classArm && $term) {
            $summaries = TermlySummary::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('student:id,tenant_id,admission_number,first_name,middle_name,last_name')
                ->orderByRaw('CASE WHEN position_in_class IS NULL THEN 1 ELSE 0 END')
                ->orderBy('position_in_class')
                ->orderBy('student_id')
                ->get();
            $publication = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('publishedBy:id,name')
                ->first();
            $activeStudents = Student::where('tenant_id', $tenantId)
                ->where('current_class_arm_id', $classArm->id)
                ->where('status', Student::STATUS_ACTIVE)
                ->count();
        }

        return response()->json([
            'contract_version' => 2,
            'module' => [
                'key' => 'reports',
                'title' => 'Report Cards',
            ],
            'capabilities' => [
                'view' => true,
                'compute' => $this->canPublish($user),
                'publish' => $this->canPublish($user),
                'unpublish' => $this->canPublish($user),
                'edit_remarks' => $this->canEditRemarks($user),
            ],
            'options' => [
                'class_arms' => $classArms->map(fn (ClassArm $arm): array => [
                    'id' => $arm->id,
                    'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                    'class_level_id' => $arm->class_level_id,
                ])->values(),
                'terms' => $terms->map(fn (Term $option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'session' => $option->session?->name,
                    'is_current' => (bool) $option->is_current,
                ])->values(),
            ],
            'selected' => [
                'class_arm_id' => $classArm?->id,
                'term_id' => $term?->id,
            ],
            'class' => $classArm ? [
                'id' => $classArm->id,
                'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
            ] : null,
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
            ] : null,
            'publication' => $this->publicationPayload($publication),
            'summary' => [
                'computed' => $summaries->count(),
                'active_students' => $activeStudents,
                'missing' => $activeStudents === null ? null : max(0, $activeStudents - $summaries->count()),
                'published' => $publication?->isPublished() ?? false,
            ],
            'students' => $summaries->map(fn (TermlySummary $summary): array => $this->summaryPayload($summary))->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function compute(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can compute report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId);

        $published = ReportCardPublication::where('tenant_id', $tenantId)
            ->where('class_arm_id', (int) $data['class_arm_id'])
            ->where('term_id', (int) $data['term_id'])
            ->where('status', 'published')
            ->exists();
        abort_if($published, 423, 'These report cards are published. Return them to draft before recomputing.');

        $computed = $this->computation->compute(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
        );

        return response()->json([
            'message' => "{$computed} report card(s) computed.",
            'computed' => $computed,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can publish report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId, includeNote: true);

        $result = $this->publication->publish(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
            $user,
            isset($data['note']) ? (string) $data['note'] : null,
            $request,
        );

        return response()->json([
            'message' => 'Report cards published. Parent and student result access is now unlocked.',
            'publication' => $this->publicationPayload($result['publication']),
            'guardians_notified' => $result['guardians_notified'],
        ]);
    }

    public function unpublish(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can unpublish report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId);

        $publication = $this->publication->unpublish(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
            $user,
            $request,
        );

        return response()->json([
            'message' => 'Report cards returned to draft. Score entry is unlocked again.',
            'publication' => $this->publicationPayload($publication),
        ]);
    }

    /**
     * Native mobile report-card PDF. It deliberately uses the same report view
     * and calculations as the web report card, but authorizes with the mobile
     * report aliases (reports / report-cards / results) instead of requiring a
     * browser session or a single legacy module key.
     */
    public function pdf(Request $request, TermlySummary $summary)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        abort_unless((int) $summary->tenant_id === $tenantId, 403);

        $student = Student::where('tenant_id', $tenantId)->findOrFail($summary->student_id);
        $term = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->findOrFail($summary->term_id);
        $classArm = ClassArm::with('classLevel', 'formTutor')
            ->where('tenant_id', $tenantId)
            ->findOrFail($summary->class_arm_id ?: $student->current_class_arm_id);
        abort_unless((int) $summary->class_arm_id === (int) $classArm->id, 403);

        $session = $term->session;
        $tenant = $user->tenant;
        $termName = strtolower($term->name);
        $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');
        $orientation = $isThirdTerm ? 'landscape' : 'portrait';

        $assessmentTypes = AssessmentType::where('term_id', $term->id)
            ->orderBy('is_exam')
            ->orderBy('name')
            ->get();
        $rawScores = Score::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->get();
        $subjects = Subject::where('tenant_id', $tenantId)
            ->whereIn('id', $rawScores->pluck('subject_id')->unique())
            ->orderBy('name')
            ->get();
        $gradingSystem = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        $classmateIds = Student::where('tenant_id', $tenantId)
            ->where('current_class_arm_id', $classArm->id)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id');
        $classScores = Score::whereIn('student_id', $classmateIds)
            ->where('term_id', $term->id)
            ->get();

        $subjectRows = [];
        foreach ($subjects as $subject) {
            $subScores = $rawScores->where('subject_id', $subject->id);
            $total = round($subScores->sum('score'), 1);
            $grade = $gradingSystem
                ->filter(fn ($item) => $total >= $item->min_score && $total <= $item->max_score)
                ->first();

            $scoresKeyed = [];
            foreach ($subScores as $score) {
                $scoresKeyed[$score->assessment_type_id] = $score->score;
            }

            $classTotals = [];
            foreach ($classmateIds as $classmateId) {
                $classTotal = $classScores
                    ->where('student_id', $classmateId)
                    ->where('subject_id', $subject->id)
                    ->sum('score');
                if ($classTotal > 0) {
                    $classTotals[] = $classTotal;
                }
            }

            $classAverage = count($classTotals) > 0
                ? round(array_sum($classTotals) / count($classTotals), 2)
                : null;
            $subjectPosition = '—';
            if (count($classTotals) > 0) {
                arsort($classTotals);
                $rank = array_search($total, array_values($classTotals));
                $subjectPosition = $rank !== false ? $rank + 1 : '—';
            }

            $row = [
                'subject_name' => $subject->name,
                'scores' => $scoresKeyed,
                'total' => $total,
                'grade' => $grade?->grade_letter ?? '—',
                'remark' => $grade?->remark ?? '—',
                'is_pass' => $grade?->is_pass_grade ?? false,
                'class_highest' => count($classTotals) ? round(max($classTotals), 1) : '—',
                'class_lowest' => count($classTotals) ? round(min($classTotals), 1) : '—',
                'class_avg' => $classAverage !== null ? number_format($classAverage, 2) : '—',
                'class_position' => $subjectPosition,
                'class_count' => count($classmateIds),
            ];

            if ($isThirdTerm) {
                $allTerms = Term::where('tenant_id', $tenantId)
                    ->where('session_id', $term->session_id)
                    ->orderBy('start_date')
                    ->get();
                foreach ($allTerms as $index => $sessionTerm) {
                    $termScores = Score::where('student_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->where('term_id', $sessionTerm->id)
                        ->get();
                    $row['term'.($index + 1).'_avg'] = round($termScores->sum('score'), 1);
                }
                $annualTotal = ($row['term1_avg'] ?? 0) + ($row['term2_avg'] ?? 0) + ($row['term3_avg'] ?? 0);
                $row['annual_total'] = round($annualTotal, 1);
                $row['cumulative_avg'] = round($annualTotal / 3, 1);
                $annualGrade = $gradingSystem
                    ->filter(fn ($item) => $row['cumulative_avg'] >= $item->min_score && $row['cumulative_avg'] <= $item->max_score)
                    ->first();
                $row['grade'] = $annualGrade?->grade_letter ?? '—';
                $row['remark'] = $annualGrade?->remark ?? '—';
                $row['is_pass'] = $annualGrade?->is_pass_grade ?? false;
            }

            $subjectRows[] = $row;
        }

        $psychomotorSkills = SkillDefinition::where('tenant_id', $tenantId)
            ->where('category', 'psychomotor')
            ->get();
        $affectiveSkills = SkillDefinition::where('tenant_id', $tenantId)
            ->where('category', 'affective')
            ->get();
        $skillRatings = StudentSkillRating::where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->get();

        if (empty($summary->principal_remark)) {
            $remark = PrincipalRemarkService::generate(
                average: $summary->final_average,
                position: $summary->position_in_class,
                totalStudents: $summary->total_students_in_class,
                subjectsFailed: $summary->subjects_failed,
                studentName: $student->first_name,
                rotationSeed: $student->id,
            );
            $summary->update(['principal_remark' => $remark]);
            $summary->refresh();
        }

        $attendanceSummary = [];
        if (class_exists('\App\Models\AttendanceRecord')) {
            $present = \App\Models\AttendanceRecord::where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->whereIn('status', ['present', 'late'])
                ->count();
            $absent = \App\Models\AttendanceRecord::where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->where('status', 'absent')
                ->count();
            $daysOpen = \App\Models\AttendanceRecord::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->distinct('attendance_date')
                ->count('attendance_date');
            $attendanceSummary = [
                'days_open' => $daysOpen ?: '—',
                'days_present' => $present,
                'days_absent' => $absent,
                'rate' => $daysOpen > 0 ? round(($present / $daysOpen) * 100) : '—',
            ];
        }

        $classSummaries = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->get();
        $summaries_class_avg = $classSummaries->count() > 0
            ? number_format($classSummaries->avg('final_average'), 2)
            : null;

        $pdf = Pdf::loadView('reports.pdf', compact(
            'student', 'classArm', 'term', 'session', 'tenant',
            'summary', 'isThirdTerm', 'assessmentTypes', 'subjectRows',
            'gradingSystem', 'psychomotorSkills', 'affectiveSkills', 'skillRatings',
            'attendanceSummary', 'summaries_class_avg',
        ))->setPaper('a4', $orientation);

        $filename = 'ReportCard_'.str_replace(' ', '_', $student->full_name).'_'.str_replace(' ', '_', $term->name).'.pdf';

        return $pdf->download($filename);
    }

    private function validateSelection(Request $request, int $tenantId, bool $includeNote = false): array
    {
        $rules = [
            'class_arm_id' => [
                'required', 'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'term_id' => [
                'required', 'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
        if ($includeNote) {
            $rules['note'] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function summaryPayload(TermlySummary $summary): array
    {
        $student = $summary->student;

        return [
            'summary_id' => $summary->id,
            'student' => [
                'id' => $summary->student_id,
                'name' => $student
                    ? trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])))
                    : 'Student #'.$summary->student_id,
                'admission_number' => $student?->admission_number,
            ],
            'average' => (float) $summary->final_average,
            'position' => $summary->position_in_class,
            'class_size' => $summary->total_students_in_class,
            'subjects_offered' => (int) $summary->subjects_offered,
            'subjects_failed' => (int) $summary->subjects_failed,
            'promotion_status' => $summary->promotion_status,
            'form_tutor_remark' => $summary->form_tutor_remark,
            'principal_remark' => $summary->principal_remark,
            'subjects' => $summary->subject_breakdown ?: [],
            'computed_at' => $summary->computed_at?->toIso8601String(),
        ];
    }

    private function publicationPayload(?ReportCardPublication $publication): ?array
    {
        if (!$publication) {
            return null;
        }

        return [
            'id' => $publication->id,
            'status' => $publication->status,
            'published_at' => $publication->published_at?->toIso8601String(),
            'published_by' => $publication->published_by,
            'published_by_name' => $publication->publishedBy?->name,
            'archived_at' => $publication->archived_at?->toIso8601String(),
            'note' => $publication->note,
        ];
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403);
        abort_unless($user->tenant_id, 403);
        abort_unless($this->canViewReports($user), 403, 'Report-card access is required for this workspace.');

        return $user;
    }

    private function canViewReports(User $user): bool
    {
        if (in_array($user->roleKey(), self::ADMIN_ROLES, true)) {
            return true;
        }

        return $user->canAccessExactModule('reports')
            || $user->canAccessExactModule('report-cards')
            || $user->canAccessExactModule('results');
    }

    private function canPublish(User $user): bool
    {
        return in_array($user->roleKey(), self::ADMIN_ROLES, true)
            || $user->canAccessExactModule('students');
    }

    private function canEditRemarks(User $user): bool
    {
        return in_array($user->roleKey(), self::ADMIN_ROLES, true)
            || $user->canAccessModule('reports.remarks');
    }
}
