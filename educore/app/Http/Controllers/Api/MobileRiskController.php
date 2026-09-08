<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\RiskThresholdConfig;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentRiskFlag;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Native student-risk intelligence contract.
 *
 * All selectors and flag mutations are explicitly tenant-qualified. The risk
 * computation mirrors the existing web RiskFlagController so mobile triage
 * does not invent a second scoring model.
 */
class MobileRiskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;

        $validated = $request->validate([
            'term_id' => [
                'nullable',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', Rule::in(['all', 'open', 'acknowledged', 'resolved'])],
            'risk_level' => ['nullable', Rule::in(['all', 'critical', 'high', 'medium', 'low'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $terms = Term::query()
            ->where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();
        $selectedTermId = (int) ($validated['term_id']
            ?? optional($terms->firstWhere('is_current', true))->id
            ?? optional($terms->first())->id
            ?? 0);
        $selectedStatus = $validated['status'] ?? 'open';
        $selectedLevel = $validated['risk_level'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 30);

        $flags = collect();
        $meta = [
            'page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'last_page' => 1,
            'has_more' => false,
        ];

        if ($selectedTermId > 0) {
            $query = StudentRiskFlag::query()
                ->where('tenant_id', $tenantId)
                ->where('term_id', $selectedTermId)
                ->with([
                    'student.currentClassArm.classLevel:id,name',
                    'classArm.classLevel:id,name',
                    'term:id,name',
                ])
                ->when($selectedStatus !== 'all', fn ($builder) => $builder->where('status', $selectedStatus))
                ->when($selectedLevel !== 'all', fn ($builder) => $builder->where('risk_level', $selectedLevel))
                ->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                ->orderByDesc('composite_risk')
                ->orderByDesc('computed_at');

            $page = $query->paginate($perPage)->withQueryString();
            $flags = collect($page->items())->map(fn (StudentRiskFlag $flag) => $this->flagPayload($flag));
            $meta = [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'has_more' => $page->hasMorePages(),
            ];
        }

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'risk',
                'title' => 'Risk Flags',
                'description' => 'Academic, attendance and fee-risk intelligence for early intervention',
                'mobile_policy' => 'native_manage',
            ],
            'capabilities' => [
                'compute' => true,
                'acknowledge' => true,
                'resolve' => true,
                'manage_config' => true,
            ],
            'filters' => [
                'terms' => $terms->map(fn (Term $term) => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'session_name' => $term->session?->name,
                    'current' => (bool) $term->is_current,
                ])->values(),
                'statuses' => [
                    ['key' => 'open', 'label' => 'Open'],
                    ['key' => 'acknowledged', 'label' => 'Acknowledged'],
                    ['key' => 'resolved', 'label' => 'Resolved'],
                    ['key' => 'all', 'label' => 'All'],
                ],
                'risk_levels' => [
                    ['key' => 'all', 'label' => 'All levels'],
                    ['key' => 'critical', 'label' => 'Critical'],
                    ['key' => 'high', 'label' => 'High'],
                    ['key' => 'medium', 'label' => 'Medium'],
                    ['key' => 'low', 'label' => 'Low'],
                ],
            ],
            'selected' => [
                'term_id' => $selectedTermId ?: null,
                'status' => $selectedStatus,
                'risk_level' => $selectedLevel,
            ],
            'summary' => $this->summary($tenantId, $selectedTermId),
            'config' => $this->configPayload(RiskThresholdConfig::forTenant($tenantId)),
            'flags' => $flags->values(),
            'meta' => $meta,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function show(Request $request, int $flag): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;
        $record = $this->flag($tenantId, $flag)->load([
            'student.currentClassArm.classLevel:id,name',
            'classArm.classLevel:id,name',
            'term.session:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
        ]);

        $attendance = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $record->student_id)
            ->where('term_id', $record->term_id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
        $totalAttendance = (int) $attendance->sum();
        $present = (int) $attendance->get('present', 0);
        $attendanceRate = $totalAttendance > 0 ? round(($present / $totalAttendance) * 100, 1) : null;

        $scoreQuery = Score::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $record->student_id)
            ->where('term_id', $record->term_id);
        $scoreCount = (clone $scoreQuery)->count();
        $scoreAverage = $scoreCount > 0 ? round((float) (clone $scoreQuery)->avg('score'), 1) : null;

        $invoiceQuery = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $record->student_id)
            ->where('term_id', $record->term_id)
            ->whereIn('status', ['unpaid', 'partially_paid']);
        $outstandingInvoices = (clone $invoiceQuery)->count();
        $outstandingBalance = (float) (clone $invoiceQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(total_amount - amount_paid, 0)), 0) as balance')
            ->value('balance');

        $previous = TermlySummary::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $record->student_id)
            ->where('term_id', '!=', $record->term_id)
            ->latest('computed_at')
            ->first();

        return response()->json([
            'contract_version' => 1,
            'flag' => $this->flagPayload($record, includeActions: true),
            'context' => [
                'attendance_total' => $totalAttendance,
                'attendance_present' => $present,
                'attendance_rate' => $attendanceRate,
                'score_records' => $scoreCount,
                'score_average' => $scoreAverage,
                'previous_term_average' => $previous ? (float) $previous->final_average : null,
                'outstanding_invoices' => $outstandingInvoices,
                'outstanding_balance' => $outstandingBalance,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function compute(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_level_id' => [
                'nullable',
                'integer',
                Rule::exists('class_levels', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_arm_id' => [
                'nullable',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $term = Term::query()->where('tenant_id', $tenantId)->findOrFail($data['term_id']);
        $config = RiskThresholdConfig::forTenant($tenantId);
        $students = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->when($data['class_arm_id'] ?? null, fn ($query, $armId) => $query->where('current_class_arm_id', $armId))
            ->when(
                empty($data['class_arm_id']) && ! empty($data['class_level_id']),
                function ($query) use ($data, $tenantId) {
                    $armIds = ClassArm::query()
                        ->where('tenant_id', $tenantId)
                        ->where('class_level_id', $data['class_level_id'])
                        ->pluck('id');
                    $query->whereIn('current_class_arm_id', $armIds);
                }
            )
            ->get();

        $created = 0;
        $updated = 0;
        $cleared = 0;

        foreach ($students as $student) {
            $result = $this->computeStudentRisk($student, $term, $config, $tenantId);
            $existing = StudentRiskFlag::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->first();

            if ($result['composite_risk'] === 0 && $result['risk_level'] === 'low' && empty($result['flags'])) {
                if ($existing && $existing->status === 'open') {
                    $existing->delete();
                    $cleared++;
                }
                continue;
            }

            if ($existing) {
                $existing->update([...$result, 'computed_at' => now()]);
                $updated++;
            } else {
                StudentRiskFlag::create([
                    ...$result,
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'class_arm_id' => $student->current_class_arm_id,
                    'computed_at' => now(),
                    'status' => 'open',
                ]);
                $created++;
            }
        }

        return response()->json([
            'message' => "Risk analysis complete — {$created} new flags, {$updated} updated".($cleared ? ", {$cleared} cleared" : ''),
            'created' => $created,
            'updated' => $updated,
            'cleared' => $cleared,
            'processed' => $students->count(),
        ]);
    }

    public function acknowledge(Request $request, int $flag): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $record = $this->flag((int) $user->tenant_id, $flag);
        abort_unless($record->status === 'open', 409, 'Only open risk flags can be acknowledged.');
        $data = $request->validate(['intervention_note' => ['nullable', 'string', 'max:1000']]);

        $record->update([
            'status' => 'acknowledged',
            'intervention_note' => $data['intervention_note'] ?? null,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        return response()->json([
            'message' => 'Flag acknowledged. Intervention note saved.',
            'flag' => $this->flagPayload($record->fresh(['student.currentClassArm.classLevel', 'classArm.classLevel', 'term'])),
        ]);
    }

    public function resolve(Request $request, int $flag): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $record = $this->flag((int) $user->tenant_id, $flag);
        abort_if($record->status === 'resolved', 409, 'This risk flag is already resolved.');
        $data = $request->validate(['intervention_note' => ['nullable', 'string', 'max:1000']]);

        $record->update([
            'status' => 'resolved',
            'intervention_note' => $data['intervention_note'] ?? $record->intervention_note,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Flag marked as resolved.',
            'flag' => $this->flagPayload($record->fresh(['student.currentClassArm.classLevel', 'classArm.classLevel', 'term'])),
        ]);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $data = $request->validate([
            'academic_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'attendance_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'subjects_failed_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'include_fee_risk' => ['required', 'boolean'],
            'academic_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'attendance_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'fee_weight' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = (int) $data['academic_weight'] + (int) $data['attendance_weight'] + (int) $data['fee_weight'];
        if ($total !== 100) {
            throw ValidationException::withMessages([
                'weights' => ["Weights must sum to 100 (currently {$total})."],
            ]);
        }

        $config = RiskThresholdConfig::forTenant((int) $user->tenant_id);
        $config->update($data);

        return response()->json([
            'message' => 'Risk thresholds updated.',
            'config' => $this->configPayload($config->fresh()),
        ]);
    }

    private function authorizedUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School risk access required.');
        abort_unless($user->tenant_id && $user->canAccessModule('risk'), 403, 'You do not have access to risk intelligence.');

        return $user;
    }

    private function flag(int $tenantId, int $id): StudentRiskFlag
    {
        return StudentRiskFlag::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
    }

    private function summary(int $tenantId, int $termId): array
    {
        if ($termId <= 0) {
            return array_fill_keys(['total', 'critical', 'high', 'medium', 'low', 'open', 'acknowledged', 'resolved'], 0);
        }

        $summary = StudentRiskFlag::query()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN risk_level='critical' THEN 1 ELSE 0 END) as critical, SUM(CASE WHEN risk_level='high' THEN 1 ELSE 0 END) as high, SUM(CASE WHEN risk_level='medium' THEN 1 ELSE 0 END) as medium, SUM(CASE WHEN risk_level='low' THEN 1 ELSE 0 END) as low, SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as open, SUM(CASE WHEN status='acknowledged' THEN 1 ELSE 0 END) as acknowledged, SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved")
            ->first();

        return collect(['total', 'critical', 'high', 'medium', 'low', 'open', 'acknowledged', 'resolved'])
            ->mapWithKeys(fn ($key) => [$key => (int) ($summary?->{$key} ?? 0)])
            ->all();
    }

    private function flagPayload(StudentRiskFlag $flag, bool $includeActions = false): array
    {
        $student = $flag->student;
        $classArm = $flag->classArm ?: $student?->currentClassArm;
        $payload = [
            'id' => $flag->id,
            'student' => [
                'id' => $student?->id,
                'name' => $student?->full_name ?? 'Student',
                'admission_number' => $student?->admission_number,
                'class_name' => $classArm?->full_name,
            ],
            'term' => [
                'id' => $flag->term?->id ?? $flag->term_id,
                'name' => $flag->term?->name,
            ],
            'academic_risk' => (int) $flag->academic_risk,
            'attendance_risk' => (int) $flag->attendance_risk,
            'fee_risk' => (int) $flag->fee_risk,
            'subjects_failed' => (int) $flag->subjects_failed,
            'composite_risk' => (int) $flag->composite_risk,
            'risk_level' => $flag->risk_level,
            'flags' => $flag->flags ?? [],
            'flag_labels' => $flag->flagLabels(),
            'status' => $flag->status,
            'intervention_note' => $flag->intervention_note,
            'computed_at' => $flag->computed_at?->toIso8601String(),
            'acknowledged_at' => $flag->acknowledged_at?->toIso8601String(),
            'resolved_at' => $flag->resolved_at?->toIso8601String(),
        ];

        if ($includeActions) {
            $payload['acknowledged_by'] = $flag->acknowledgedBy?->name;
            $payload['resolved_by'] = $flag->resolvedBy?->name;
        }

        return $payload;
    }

    private function configPayload(RiskThresholdConfig $config): array
    {
        return [
            'academic_threshold' => (float) $config->academic_threshold,
            'attendance_threshold' => (float) $config->attendance_threshold,
            'subjects_failed_threshold' => (int) $config->subjects_failed_threshold,
            'include_fee_risk' => (bool) $config->include_fee_risk,
            'academic_weight' => (int) $config->academic_weight,
            'attendance_weight' => (int) $config->attendance_weight,
            'fee_weight' => (int) $config->fee_weight,
        ];
    }

    /** Mirrors RiskFlagController::computeStudentRisk. */
    private function computeStudentRisk(Student $student, Term $term, RiskThresholdConfig $config, int $tenantId): array
    {
        $flags = [];
        $academicRisk = 0;
        $attendanceRisk = 0;
        $feeRisk = 0;

        $summary = TermlySummary::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        if ($summary) {
            $average = (float) $summary->final_average;
            $subjectsFailed = (int) $summary->subjects_failed;

            if ($average < 30) {
                $academicRisk = 100;
                $flags[] = 'avg_critically_low';
            } elseif ($average < $config->academic_threshold) {
                $academicRisk = (int) round((($config->academic_threshold - $average) / $config->academic_threshold) * 100);
                $flags[] = 'avg_below_threshold';
            }

            if ($subjectsFailed >= $config->subjects_failed_threshold) {
                $flags[] = 'subjects_failed';
                $academicRisk = max($academicRisk, min(100, $academicRisk + ($subjectsFailed * 10)));
            }
        } else {
            $scoreCount = Score::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->count();
            if ($scoreCount === 0) {
                $flags[] = 'no_scores_recorded';
                $academicRisk = 30;
            }
        }

        $subjectsFailed = $summary ? (int) $summary->subjects_failed : 0;
        $attendance = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
        $totalDays = $attendance->sum();
        $presentDays = (int) $attendance->get('present', 0);

        if ($totalDays > 0) {
            $presenceRate = ($presentDays / $totalDays) * 100;
            if ($presenceRate < 50) {
                $attendanceRisk = 100;
                $flags[] = 'critical_absenteeism';
            } elseif ($presenceRate < $config->attendance_threshold) {
                $attendanceRisk = (int) round((($config->attendance_threshold - $presenceRate) / $config->attendance_threshold) * 100);
                $flags[] = 'high_absenteeism';
            }
        } else {
            $flags[] = 'no_attendance_recorded';
            $attendanceRisk = 20;
        }

        if ($config->include_fee_risk) {
            $outstandingCount = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->where('term_id', $term->id)
                ->count();
            if ($outstandingCount > 0) {
                $feeRisk = 70;
                $flags[] = 'fees_overdue';
            }
        }

        $composite = (int) round(
            ($academicRisk * $config->academic_weight / 100)
            + ($attendanceRisk * $config->attendance_weight / 100)
            + ($feeRisk * $config->fee_weight / 100)
        );
        $riskLevel = match (true) {
            $composite >= 70 => 'critical',
            $composite >= 50 => 'high',
            $composite >= 25 => 'medium',
            default => 'low',
        };

        return [
            'academic_risk' => $academicRisk,
            'attendance_risk' => $attendanceRisk,
            'fee_risk' => $feeRisk,
            'subjects_failed' => $subjectsFailed,
            'composite_risk' => $composite,
            'risk_level' => $riskLevel,
            'flags' => $flags,
        ];
    }
}
