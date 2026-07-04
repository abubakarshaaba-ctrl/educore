<?php

namespace App\Console\Commands;

use App\Models\Scopes\TenantContext;
use App\Services\AcademicCycleService;
use Illuminate\Console\Command;
use Throwable;

class RepairAcademicCurrentState extends Command
{
    protected $signature = 'academic:repair-current-state
        {--tenant= : Tenant ID}
        {--dry-run : Preview current-state issues without writes}
        {--commit : Reserved for future approved write mode}';

    protected $description = 'Detect current-session, current-term, and current-enrolment inconsistencies.';

    public function __construct(private AcademicCycleService $academicCycle)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('commit')) {
            $this->error('Repair runs in review-only mode and does not write changes. Use --dry-run to inspect issues.');

            return self::FAILURE;
        }

        try {
            $tenantId = $this->requiredIntegerOption('tenant');
            TenantContext::set($tenantId);

            $this->info('Dry run only. No academic records will be modified.');
            $decision = $this->academicCycle->repairCurrentStateAnalysis($tenantId);

            $this->table(['Type', 'Message'], collect($decision->allItems())
                ->flatMap(fn (array $items, string $type) => collect($items)->map(fn ($item) => [$type, $item]))
                ->values()
                ->all());
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Current-state repair inspection failed: ' . $exception->getMessage());

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
