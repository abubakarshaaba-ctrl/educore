<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Services\LifecycleAuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class RepairStudentEnrollments extends Command
{
    protected $signature = 'lifecycle:repair-student-enrollments
        {--dry-run : Preview repairs without changing data}
        {--apply : Apply safe repairs}
        {--tenant= : Limit to one tenant ID}
        {--student= : Limit to one student ID}';

    protected $description = 'Detect and safely repair current student enrollment pointers.';

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        $dryRun = !$this->option('apply');

        try {
            $tenantId = $this->integerOption('tenant');
            $studentId = $this->integerOption('student');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($tenantId && $studentId) {
            $studentMatchesTenant = Student::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereKey($studentId)
                ->exists();

            if (!$studentMatchesTenant) {
                $this->error('The supplied --student does not belong to the supplied --tenant.');

                return self::FAILURE;
            }
        }

        $repairMetric = $dryRun ? 'would_repair' : 'repaired';

        $summary = [
            'inspected' => 0,
            'valid' => 0,
            $repairMetric => 0,
            'unresolved' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $candidateRows = [];

        $this->info($dryRun
            ? 'Dry run only. No student_enrollments records will be modified.'
            : 'Applying safe student enrollment repairs.');

        $query = Student::withoutTenantScope()
            ->with('currentClassArm')
            ->whereNotNull('current_class_arm_id')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($studentId, fn ($q) => $q->where('id', $studentId));

        foreach ($query->lazyById(100) as $student) {
            $this->processStudent($student, $dryRun, $summary, $candidateRows);
        }

        if ($dryRun && $candidateRows !== []) {
            $this->newLine();
            $this->table([
                'Tenant',
                'Student',
                'Admission No.',
                'Name',
                'Current Arm',
                'Enrollment State',
                'Active Session',
                'Active Term',
                'Proposed Action',
                'Reason',
                'Safety',
            ], $candidateRows);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($count, $key) => [
            str_replace('_', ' ', $key),
            $count,
        ])->values()->all());

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processStudent(Student $student, bool $dryRun, array &$summary, array &$candidateRows): void
    {
        $summary['inspected']++;

        try {
            if ($dryRun) {
                $analysis = $this->analyseStudent($student);
                $this->recordDryRunSummary($student, $analysis, $summary, $candidateRows);

                return;
            }

            DB::transaction(function () use ($student, &$summary): void {
                $lockedStudent = Student::withoutTenantScope()
                    ->where('tenant_id', $student->tenant_id)
                    ->whereKey($student->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedStudent || !$lockedStudent->current_class_arm_id) {
                    $summary['skipped']++;

                    return;
                }

                $analysis = $this->analyseStudent($lockedStudent, true);

                if ($analysis['state'] === 'valid') {
                    $summary['valid']++;

                    return;
                }

                if ($analysis['state'] === 'unresolved') {
                    $summary['unresolved']++;
                    $this->warn($this->studentLabel($lockedStudent) . ' unresolved: ' . $analysis['resolution']);

                    return;
                }

                $enrollment = $this->applyRepair($lockedStudent, $analysis);
                $summary['repaired']++;

                $this->auditLogger->record(
                    $lockedStudent->tenant_id,
                    null,
                    $lockedStudent,
                    'student_enrollment.repaired',
                    [
                        'issues' => $analysis['issues'],
                        'current_class_arm_id' => $lockedStudent->current_class_arm_id,
                    ],
                    [
                        'current_enrollment_id' => $enrollment->id,
                        'class_arm_id' => $enrollment->class_arm_id,
                        'session_id' => $enrollment->session_id,
                        'term_id' => $enrollment->term_id,
                    ],
                    'CLI student enrollment repair'
                );

                $this->line($this->studentLabel($lockedStudent) . ' repaired.');
            });
        } catch (Throwable $exception) {
            $summary['failed']++;
            $this->error($this->studentLabel($student) . ' failed: ' . $exception->getMessage());
        }
    }

    private function analyseStudent(Student $student, bool $lock = false): array
    {
        $enrollmentQuery = StudentEnrollment::withoutTenantScope()
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->orderByDesc('id');

        if ($lock) {
            $enrollmentQuery->lockForUpdate();
        }

        $enrollments = $enrollmentQuery->get();
        $current = $enrollments->where('is_current', true)->values();
        $matching = $enrollments
            ->where('class_arm_id', (int) $student->current_class_arm_id)
            ->sortByDesc('id')
            ->values();

        $issues = [];

        if ($matching->isEmpty()) {
            $issues[] = 'missing_matching_enrollment';
        }

        if ($current->isEmpty()) {
            $issues[] = 'no_current_enrollment';
        } elseif ($current->count() > 1) {
            $issues[] = 'multiple_current_enrollments';
        } elseif ((int) $current->first()->class_arm_id !== (int) $student->current_class_arm_id) {
            $issues[] = 'current_enrollment_class_mismatch';
        }

        if ($issues === []) {
            return [
                'state' => 'valid',
                'issues' => [],
                'resolution' => 'No repair needed.',
                'enrollments' => $enrollments,
                'matching' => $matching,
                'active_context' => null,
            ];
        }

        $activeContext = $this->activeAcademicContext($student->tenant_id);
        if (!$activeContext) {
            return [
                'state' => 'unresolved',
                'issues' => $issues,
                'resolution' => 'Cannot identify exactly one active academic session and active term for tenant.',
                'enrollments' => $enrollments,
                'matching' => $matching,
                'active_context' => null,
            ];
        }

        return [
            'state' => 'repairable',
            'issues' => $issues,
            'resolution' => $matching->isNotEmpty()
                ? 'Would mark the newest matching enrollment current and close other current flags.'
                : 'Would create a missing enrollment using the tenant active session and term.',
            'enrollments' => $enrollments,
            'matching' => $matching,
            'active_context' => $activeContext,
        ];
    }

    private function applyRepair(Student $student, array $analysis): StudentEnrollment
    {
        StudentEnrollment::withoutTenantScope()
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->update(['is_current' => false]);

        if ($analysis['matching']->isNotEmpty()) {
            $enrollment = StudentEnrollment::withoutTenantScope()
                ->where('tenant_id', $student->tenant_id)
                ->whereKey($analysis['matching']->first()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $enrollment->forceFill([
                'is_current' => true,
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'end_date' => null,
                'ended_by' => null,
                'ended_reason' => null,
            ])->save();

            return $enrollment;
        }

        $context = $analysis['active_context'];

        return StudentEnrollment::withoutTenantScope()->create([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'class_arm_id' => $student->current_class_arm_id,
            'session_id' => $context['session']->id,
            'term_id' => $context['term']->id,
            'start_date' => $this->repairStartDate($context['term']),
            'end_date' => null,
            'is_current' => true,
            'status' => StudentEnrollment::STATUS_ACTIVE,
            'created_by' => null,
        ]);
    }

    private function activeAcademicContext(int $tenantId): ?array
    {
        $sessions = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->get();

        if ($sessions->count() !== 1) {
            return null;
        }

        $terms = Term::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $sessions->first()->id)
            ->where('is_current', true)
            ->get();

        if ($terms->count() !== 1) {
            return null;
        }

        return [
            'session' => $sessions->first(),
            'term' => $terms->first(),
        ];
    }

    private function repairStartDate(Term $term): string
    {
        $today = CarbonImmutable::today();

        if ($term->start_date && $term->end_date) {
            $start = CarbonImmutable::parse($term->start_date);
            $end = CarbonImmutable::parse($term->end_date);

            if ($today->betweenIncluded($start, $end)) {
                return $today->toDateString();
            }
        }

        return $term->start_date
            ? CarbonImmutable::parse($term->start_date)->toDateString()
            : $today->toDateString();
    }

    private function recordDryRunSummary(Student $student, array $analysis, array &$summary, array &$candidateRows): void
    {
        if ($analysis['state'] === 'valid') {
            $summary['valid']++;

            return;
        }

        if ($analysis['state'] === 'unresolved') {
            $summary['unresolved']++;
        } else {
            $summary['would_repair']++;
        }

        $candidateRows[] = $this->candidateRow($student, $analysis);

        $this->line(sprintf(
            '%s: %s; issues=%s; %s',
            $this->studentLabel($student),
            $analysis['state'],
            implode(',', $analysis['issues']),
            $analysis['resolution']
        ));
    }

    private function candidateRow(Student $student, array $analysis): array
    {
        $context = $analysis['active_context'];

        return [
            $student->tenant_id,
            $student->id,
            $student->admission_number ?? 'N/A',
            $student->full_name ?: 'N/A',
            $student->current_class_arm_id,
            $this->enrollmentState($analysis),
            $context ? $context['session']->id . ' - ' . $context['session']->name : 'unresolved',
            $context ? $context['term']->id . ' - ' . $context['term']->name : 'unresolved',
            $analysis['resolution'],
            implode(', ', $analysis['issues']),
            $analysis['state'] === 'repairable' ? 'safely repairable' : 'unresolved',
        ];
    }

    private function enrollmentState(array $analysis): string
    {
        $enrollments = $analysis['enrollments'];
        $currentIds = $enrollments->where('is_current', true)->pluck('id')->implode(',');
        $matchingIds = $analysis['matching']->pluck('id')->implode(',');

        return sprintf(
            'total=%d; current=%s; matching=%s',
            $enrollments->count(),
            $currentIds !== '' ? $currentIds : 'none',
            $matchingIds !== '' ? $matchingIds : 'none'
        );
    }

    private function studentLabel(Student $student): string
    {
        return sprintf('tenant=%s student=%s', $student->tenant_id, $student->id);
    }

    private function integerOption(string $name): ?int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value < 1) {
            throw new InvalidArgumentException("--{$name} must be a positive integer.");
        }

        return (int) $value;
    }
}
