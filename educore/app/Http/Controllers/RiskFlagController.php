<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Invoice;
use App\Models\RiskThresholdConfig;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentRiskFlag;
use App\Models\Term;
use App\Models\TermlySummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskFlagController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $terms      = Term::with('session')->latest()->get();
        $classLevels= ClassLevel::with('classArms')->orderBy('order_index')->get();
        $currentTerm= $terms->firstWhere('is_current', true);
        $config     = RiskThresholdConfig::forTenant(auth()->user()->tenant_id);

        $selectedTermId = $request->get('term_id', optional($currentTerm)->id);
        $selectedLevel  = $request->get('class_level_id');
        $selectedArm    = $request->get('class_arm_id');
        $selectedStatus = $request->get('status', 'open');
        $selectedLevel_ = $request->get('risk_level');

        // Query flags
        $query = StudentRiskFlag::with(['student.currentClassArm.classLevel', 'term'])
                    ->where('term_id', $selectedTermId);

        if ($selectedArm) {
            $query->where('class_arm_id', $selectedArm);
        } elseif ($selectedLevel) {
            $armIds = ClassArm::where('class_level_id', $selectedLevel)->pluck('id');
            $query->whereIn('class_arm_id', $armIds);
        }

        if ($selectedStatus) $query->where('status', $selectedStatus);
        if ($selectedLevel_) $query->where('risk_level', $selectedLevel_);

        $flags = $query->orderByRaw("FIELD(risk_level,'critical','high','medium','low')")
                       ->orderByDesc('composite_risk')
                       ->paginate(30)->withQueryString();

        // Summary counts for selected term
        $summary = StudentRiskFlag::where('term_id', $selectedTermId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN risk_level='critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN risk_level='high' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN risk_level='medium' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status='acknowledged' THEN 1 ELSE 0 END) as acknowledged,
                SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved
            ")->first();

        return view('risk.index', compact(
            'flags', 'terms', 'classLevels', 'config', 'summary',
            'selectedTermId', 'selectedLevel', 'selectedArm',
            'selectedStatus', 'selectedLevel_', 'currentTerm'
        ));
    }

    // ── Detail view for one student's risk ───────────────────────────
    public function show(StudentRiskFlag $flag)
    {
        $flag->load(['student.currentClassArm.classLevel', 'term', 'acknowledgedBy', 'resolvedBy']);
        $student = $flag->student;

        // Score breakdown this term
        $scores = Score::where('student_id', $student->id)
                    ->where('term_id', $flag->term_id)
                    ->with(['subject', 'assessmentType'])
                    ->get()
                    ->groupBy('subject_id');

        // Attendance this term
        $attendance = AttendanceRecord::where('student_id', $student->id)
                        ->where('term_id', $flag->term_id)
                        ->selectRaw("status, COUNT(*) as count")
                        ->groupBy('status')
                        ->pluck('count', 'status');

        $totalDays  = $attendance->sum();
        $presentDays= $attendance->get('present', 0);
        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        // Previous term summary (for trend)
        $prevSummary = TermlySummary::where('student_id', $student->id)
                        ->where('term_id', '!=', $flag->term_id)
                        ->latest('computed_at')
                        ->first();

        // Outstanding fees
        $outstanding = Invoice::where('student_id', $student->id)
                        ->whereIn('status', ['unpaid', 'partially_paid'])
                        ->get();

        return view('risk.show', compact(
            'flag', 'student', 'scores', 'attendance',
            'totalDays', 'presentDays', 'attendanceRate',
            'prevSummary', 'outstanding'
        ));
    }

    // ── Compute / re-compute risk flags for a term ────────────────────
    public function compute(Request $request)
    {
        $data = $request->validate([
            'term_id'        => ['required', 'exists:terms,id'],
            'class_level_id' => ['nullable', 'exists:class_levels,id'],
            'class_arm_id'   => ['nullable', 'exists:class_arms,id'],
        ]);

        $term    = Term::findOrFail($data['term_id']);
        $config  = RiskThresholdConfig::forTenant(auth()->user()->tenant_id);
        $tenantId= auth()->user()->tenant_id;

        // Resolve students to process
        $query = Student::where('status', Student::STATUS_ACTIVE);
        if (!empty($data['class_arm_id'])) {
            $query->where('current_class_arm_id', $data['class_arm_id']);
        } elseif (!empty($data['class_level_id'])) {
            $armIds = ClassArm::where('class_level_id', $data['class_level_id'])->pluck('id');
            $query->whereIn('current_class_arm_id', $armIds);
        }
        $students = $query->get();

        $flagged  = 0;
        $cleared  = 0;
        $updated  = 0;

        foreach ($students as $student) {
            $result = $this->computeStudentRisk($student, $term, $config, $tenantId);

            // Upsert the flag record
            $existing = StudentRiskFlag::where('student_id', $student->id)
                            ->where('term_id', $term->id)
                            ->first();

            if ($result['composite_risk'] === 0 && $result['risk_level'] === 'low' && empty($result['flags'])) {
                // No risk — if there was an open flag, clear it
                if ($existing && $existing->status === 'open') {
                    $existing->delete();
                    $cleared++;
                }
                continue;
            }

            if ($existing) {
                // Preserve acknowledgement/resolution if already actioned
                $existing->update(array_merge($result, ['computed_at' => now()]));
                $updated++;
            } else {
                StudentRiskFlag::create(array_merge($result, [
                    'student_id'   => $student->id,
                    'term_id'      => $term->id,
                    'class_arm_id' => $student->current_class_arm_id,
                    'computed_at'  => now(),
                    'status'       => 'open',
                ]));
                $flagged++;
            }
        }

        $msg = "Risk analysis complete — {$flagged} new flags, {$updated} updated";
        if ($cleared) $msg .= ", {$cleared} cleared";

        return back()->with('success', $msg);
    }

    // ── Acknowledge a flag ────────────────────────────────────────────
    public function acknowledge(Request $request, StudentRiskFlag $flag)
    {
        $data = $request->validate([
            'intervention_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $flag->update([
            'status'             => 'acknowledged',
            'intervention_note'  => $data['intervention_note'],
            'acknowledged_by'    => auth()->id(),
            'acknowledged_at'    => now(),
        ]);

        return back()->with('success', 'Flag acknowledged. Intervention note saved.');
    }

    // ── Resolve a flag ────────────────────────────────────────────────
    public function resolve(Request $request, StudentRiskFlag $flag)
    {
        $data = $request->validate([
            'intervention_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $flag->update([
            'status'            => 'resolved',
            'intervention_note' => $data['intervention_note'] ?? $flag->intervention_note,
            'resolved_by'       => auth()->id(),
            'resolved_at'       => now(),
        ]);

        return back()->with('success', 'Flag marked as resolved.');
    }

    // ── Update threshold config ───────────────────────────────────────
    public function saveConfig(Request $request)
    {
        $data = $request->validate([
            'academic_threshold'        => ['required', 'numeric', 'min:0', 'max:100'],
            'attendance_threshold'      => ['required', 'numeric', 'min:0', 'max:100'],
            'subjects_failed_threshold' => ['required', 'integer', 'min:1'],
            'include_fee_risk'          => ['boolean'],
            'academic_weight'           => ['required', 'integer', 'min:0', 'max:100'],
            'attendance_weight'         => ['required', 'integer', 'min:0', 'max:100'],
            'fee_weight'                => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = ($data['academic_weight'] ?? 0)
               + ($data['attendance_weight'] ?? 0)
               + ($data['fee_weight'] ?? 0);

        if ($total !== 100) {
            return back()->withErrors(['weights' => "Weights must sum to 100 (currently {$total})."]);
        }

        $data['include_fee_risk'] = $request->boolean('include_fee_risk');
        $config = RiskThresholdConfig::forTenant(auth()->user()->tenant_id);
        $config->update($data);

        return back()->with('success', 'Risk thresholds updated.');
    }

    // ── Core risk computation engine ──────────────────────────────────
    private function computeStudentRisk(Student $student, Term $term, RiskThresholdConfig $config, int $tenantId): array
    {
        $flags         = [];
        $academicRisk  = 0;
        $attendanceRisk= 0;
        $feeRisk       = 0;

        // ── 1. Academic risk ────────────────────────────────────────────
        $summary = TermlySummary::where('student_id', $student->id)
                    ->where('term_id', $term->id)
                    ->first();

        if ($summary) {
            $avg            = (float) $summary->final_average;
            $subjectsFailed = (int) $summary->subjects_failed;

            if ($avg < 30) {
                $academicRisk = 100;
                $flags[]      = 'avg_critically_low';
            } elseif ($avg < $config->academic_threshold) {
                $academicRisk = (int) round((($config->academic_threshold - $avg) / $config->academic_threshold) * 100);
                $flags[]      = 'avg_below_threshold';
            }

            if ($subjectsFailed >= $config->subjects_failed_threshold) {
                $flags[] = 'subjects_failed';
                $academicRisk = max($academicRisk, min(100, $academicRisk + ($subjectsFailed * 10)));
            }
        } else {
            // No summary yet — check raw scores
            $scoreCount = Score::where('student_id', $student->id)
                            ->where('term_id', $term->id)->count();
            if ($scoreCount === 0) {
                $flags[]      = 'no_scores_recorded';
                $academicRisk = 30;
            }
        }

        $subjectsFailed = $summary ? (int) $summary->subjects_failed : 0;

        // ── 2. Attendance risk ──────────────────────────────────────────
        $att = AttendanceRecord::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status');

        $totalDays   = $att->sum();
        $presentDays = (int) $att->get('present', 0);

        if ($totalDays > 0) {
            $presenceRate = ($presentDays / $totalDays) * 100;

            if ($presenceRate < 50) {
                $attendanceRisk = 100;
                $flags[]        = 'critical_absenteeism';
            } elseif ($presenceRate < $config->attendance_threshold) {
                $attendanceRisk = (int) round((($config->attendance_threshold - $presenceRate) / $config->attendance_threshold) * 100);
                $flags[]        = 'high_absenteeism';
            }
        } else {
            $flags[]        = 'no_attendance_recorded';
            $attendanceRisk = 20;
        }

        // ── 3. Fee risk ─────────────────────────────────────────────────
        if ($config->include_fee_risk) {
            $outstandingCount = Invoice::where('student_id', $student->id)
                                ->whereIn('status', ['unpaid', 'partially_paid'])
                                ->where('term_id', $term->id)
                                ->count();

            if ($outstandingCount > 0) {
                $feeRisk = 70;
                $flags[] = 'fees_overdue';
            }
        }

        // ── 4. Composite score ──────────────────────────────────────────
        $composite = (int) round(
            ($academicRisk   * $config->academic_weight   / 100) +
            ($attendanceRisk * $config->attendance_weight / 100) +
            ($feeRisk        * $config->fee_weight        / 100)
        );

        $riskLevel = match(true) {
            $composite >= 70 => 'critical',
            $composite >= 50 => 'high',
            $composite >= 25 => 'medium',
            default          => 'low',
        };

        return [
            'academic_risk'   => $academicRisk,
            'attendance_risk' => $attendanceRisk,
            'fee_risk'        => $feeRisk,
            'subjects_failed' => $subjectsFailed,
            'composite_risk'  => $composite,
            'risk_level'      => $riskLevel,
            'flags'           => $flags,
        ];
    }
}
