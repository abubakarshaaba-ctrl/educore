<?php

namespace App\Console\Commands;

use App\Models\Scopes\TenantContext;
use App\Services\AcademicCycleService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class InspectAcademicCycle extends Command
{
    protected $signature = 'academic:inspect-cycle
        {--tenant= : Tenant ID to inspect}
        {--session= : Optional academic session ID to inspect}';

    protected $description = 'Inspect academic session, term, and closure readiness state for a tenant.';

    public function __construct(private AcademicCycleService $academicCycle)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $tenantId = $this->integerOption('tenant');
            if (!$tenantId) {
                $this->error('--tenant is required.');

                return self::FAILURE;
            }

            TenantContext::set($tenantId);

            $session = $this->academicCycle->currentSessionForTenant($tenantId);
            $term = $this->academicCycle->currentTermForTenant($tenantId);

            $this->table(['Item', 'Value'], [
                ['Tenant ID', $tenantId],
                ['Current session', $session ? "{$session->id} - {$session->name}" : 'none or ambiguous'],
                ['Current term', $term ? "{$term->id} - {$term->name}" : 'none or ambiguous'],
            ]);

            $repair = $this->academicCycle->repairCurrentStateAnalysis($tenantId);
            $this->renderDecision('Current-state repair analysis', $repair);

            if ($this->option('session')) {
                $sessionId = (int) $this->option('session');
                $this->renderDecision(
                    'Session closure readiness',
                    $this->academicCycle->sessionClosureReadiness($tenantId, $sessionId)
                );
            }
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
            $this->error('Academic-cycle inspection failed: ' . $exception->getMessage());

            return self::FAILURE;
        } finally {
            TenantContext::clear();
        }

        return self::SUCCESS;
    }

    private function renderDecision(string $title, $decision): void
    {
        $this->newLine();
        $this->info($title);
        $this->table(['Type', 'Message'], collect($decision->allItems())
            ->flatMap(fn (array $items, string $type) => collect($items)->map(fn ($item) => [$type, $item]))
            ->values()
            ->all());
    }

    private function integerOption(string $name): ?int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            throw new \InvalidArgumentException("--{$name} must be a positive integer.");
        }

        return (int) $value;
    }
}
