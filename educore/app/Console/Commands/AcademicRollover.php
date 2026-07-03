<?php

namespace App\Console\Commands;

use App\Models\Scopes\TenantContext;
use App\Services\AcademicCycleService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcademicRollover extends Command
{
    protected $signature = 'academic:rollover
        {--tenant= : Tenant ID}
        {--from= : Source academic session ID}
        {--to= : Target academic session ID}
        {--dry-run : Preview rollover without writes}
        {--commit : Commit rollover writes}';

    protected $description = 'Preview or commit a tenant-scoped student enrollment rollover.';

    public function __construct(private AcademicCycleService $academicCycle)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('commit')) {
            $this->error('Use either --dry-run or --commit, not both.');

            return self::FAILURE;
        }

        try {
            $tenantId = $this->requiredIntegerOption('tenant');
            $from = $this->requiredIntegerOption('from');
            $to = $this->requiredIntegerOption('to');
            $commit = (bool) $this->option('commit');

            TenantContext::set($tenantId);

            $this->info($commit
                ? 'Commit mode requested. Rollover writes will be executed.'
                : 'Dry run only. No student, enrolment, result, attendance, fee, or CBT record will be modified.');

            $result = $commit
                ? $this->academicCycle->commitRollover($tenantId, $from, $to, null)
                : $this->academicCycle->previewRollover($tenantId, $from, $to);

            $this->table([
                'Student',
                'Admission No.',
                'Source',
                'Decision',
                'Destination',
                'Status',
                'Blocking',
                'Warnings',
            ], collect($result->rows)->map(fn (array $row) => [
                $row['student_name'] ?? $row['student_id'] ?? '',
                $row['admission_number'] ?? '',
                $row['source_class'] ?? $row['source_class_arm_id'] ?? '',
                $row['decision_type'] ?? '',
                $row['destination_class'] ?? $row['destination_class_arm_id'] ?? '',
                $row['status'] ?? '',
                implode('; ', $row['blocking'] ?? []),
                implode('; ', $row['warnings'] ?? []),
            ])->all());

            $this->table(['Metric', 'Count'], collect($result->counts)->map(fn ($count, $key) => [
                str_replace('_', ' ', $key),
                $count,
            ])->values()->all());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Rollover failed: ' . $exception->getMessage());

            return self::FAILURE;
        } finally {
            TenantContext::clear();
        }

        return self::SUCCESS;
    }

    private function requiredIntegerOption(string $name): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            throw new \InvalidArgumentException("--{$name} is required.");
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            throw new \InvalidArgumentException("--{$name} must be a positive integer.");
        }

        return (int) $value;
    }
}
