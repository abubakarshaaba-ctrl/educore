<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\StudentEnrollment;
use App\Services\AcademicCycleService;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class AcademicCycleRepairCommandTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
    }

    public function test_repair_analysis_detects_multiple_current_sessions_terms_and_enrolments(): void
    {
        $tenant = $this->tenantFixture();
        $sessionA = $this->sessionFixture($tenant, '2025/2026', true);
        $sessionB = $this->sessionFixture($tenant, '2026/2027', true);
        $termA = $this->termFixture($tenant, $sessionA, 'First Term', true);
        $this->termFixture($tenant, $sessionB, 'Second Term', true);
        $arm = $this->classArmFixture($tenant, 'Primary 1', 1);
        $student = $this->studentFixture($tenant, $arm);
        $this->enrollmentFixture($tenant, $student, $arm, $sessionA, $termA, true);
        $this->enrollmentFixture($tenant, $student, $arm, $sessionA, $termA, true);

        $decision = app(AcademicCycleService::class)->repairCurrentStateAnalysis($tenant->id);

        $messages = implode(' ', $decision->blocking);
        $this->assertStringContainsString('Multiple current academic sessions', $messages);
        $this->assertStringContainsString('Multiple current terms', $messages);
        $this->assertStringContainsString('multiple current enrolments', $messages);
    }

    public function test_repair_command_dry_run_changes_nothing(): void
    {
        $tenant = $this->tenantFixture();
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $arm = $this->classArmFixture($tenant, 'Primary 1', 1);
        $student = $this->studentFixture($tenant, $arm);
        $this->enrollmentFixture($tenant, $student, $arm, $session, $term, true);

        $before = [
            'sessions' => AcademicSession::withoutTenantScope()->where('is_current', true)->count(),
            'enrolments' => StudentEnrollment::withoutTenantScope()->where('is_current', true)->count(),
        ];

        $this->artisan('academic:repair-current-state', ['--tenant' => $tenant->id, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame($before['sessions'], AcademicSession::withoutTenantScope()->where('is_current', true)->count());
        $this->assertSame($before['enrolments'], StudentEnrollment::withoutTenantScope()->where('is_current', true)->count());
    }

    public function test_rollover_command_defaults_to_dry_run_and_changes_nothing(): void
    {
        $tenant = $this->tenantFixture();
        $source = $this->sessionFixture($tenant, '2025/2026', true);
        $sourceTerm = $this->termFixture($tenant, $source, 'Third Term', true);
        $target = $this->sessionFixture($tenant, '2026/2027');
        $this->termFixture($tenant, $target, 'First Term');
        $arm = $this->classArmFixture($tenant, 'Primary 1', 1);
        $nextArm = $this->classArmFixture($tenant, 'Primary 2', 2);
        $student = $this->studentFixture($tenant, $arm);
        $this->enrollmentFixture($tenant, $student, $arm, $source, $sourceTerm, true);
        $this->summaryFixture($tenant, $student, $arm, $sourceTerm, 'promoted');

        $before = StudentEnrollment::withoutTenantScope()->count();

        $this->artisan('academic:rollover', [
            '--tenant' => $tenant->id,
            '--from' => $source->id,
            '--to' => $target->id,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame($before, StudentEnrollment::withoutTenantScope()->count());
        $this->assertSame($arm->id, $student->fresh()->current_class_arm_id);
        $this->assertNotSame($nextArm->id, $student->fresh()->current_class_arm_id);
    }

    public function test_rollover_commit_flag_is_required_for_writes(): void
    {
        $tenant = $this->tenantFixture();
        $source = $this->sessionFixture($tenant, '2025/2026', true);
        $target = $this->sessionFixture($tenant, '2026/2027');

        $this->artisan('academic:rollover', [
            '--tenant' => $tenant->id,
            '--from' => $source->id,
            '--to' => $target->id,
        ])->assertExitCode(0);
    }
}
