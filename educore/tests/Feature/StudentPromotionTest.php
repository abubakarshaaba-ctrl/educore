<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\TermlySummary;
use App\Services\AcademicCycleService;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private AcademicCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
        $this->service = app(AcademicCycleService::class);
    }

    public function test_promotion_decision_is_stored_on_existing_termly_summary_and_audited(): void
    {
        [$tenant, $actor, $sourceSession, $term, $arm, $student] = $this->promotionFixture();
        $this->summaryFixture($tenant, $student, $arm, $term, 'pending');

        $result = $this->service->storePromotionDecisions($tenant->id, $term->id, [
            $student->id => AcademicCycleService::DECISION_PROMOTE,
        ], $actor);

        $this->assertSame(1, $result['saved']);
        $this->assertSame('promoted', TermlySummary::withoutTenantScope()->first()->promotion_status);
        $this->assertSame(1, AuditLog::where('action', 'student_promotion.decision_saved')->count());
    }

    public function test_promotion_preview_uses_current_enrolment_as_authority(): void
    {
        [$tenant, , $sourceSession, $term, $arm, $student] = $this->promotionFixture();
        $targetSession = $this->sessionFixture($tenant, '2026/2027');
        $this->termFixture($tenant, $targetSession, 'First Term');
        $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        $this->summaryFixture($tenant, $student, $arm, $term, 'promoted');

        $result = $this->service->previewRollover($tenant->id, $sourceSession->id, $targetSession->id);

        $this->assertSame(1, $result->counts['ready']);
        $this->assertSame($student->id, $result->rows[0]['student_id']);
        $this->assertSame('Primary 1 A', $result->rows[0]['source_class']);
        $this->assertSame('Primary 2 A', $result->rows[0]['destination_class']);
    }

    public function test_missing_current_enrolment_blocks_promotion(): void
    {
        [$tenant, , $sourceSession, $term, $arm, $student] = $this->promotionFixture(false);
        $targetSession = $this->sessionFixture($tenant, '2026/2027');
        $this->termFixture($tenant, $targetSession, 'First Term');
        $this->summaryFixture($tenant, $student, $arm, $term, 'promoted');

        $result = $this->service->previewRollover($tenant->id, $sourceSession->id, $targetSession->id);

        $this->assertSame(0, $result->counts['inspected']);
    }

    public function test_multiple_current_enrolments_block_promotion(): void
    {
        [$tenant, , $sourceSession, $term, $arm, $student] = $this->promotionFixture();
        $targetSession = $this->sessionFixture($tenant, '2026/2027');
        $this->termFixture($tenant, $targetSession, 'First Term');
        $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        $this->summaryFixture($tenant, $student, $arm, $term, 'promoted');
        StudentEnrollment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'class_arm_id' => $arm->id,
            'session_id' => $sourceSession->id,
            'term_id' => $term->id,
            'is_current' => true,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

        $result = $this->service->previewRollover($tenant->id, $sourceSession->id, $targetSession->id);

        $this->assertSame(2, $result->counts['blocked']);
        $this->assertStringContainsString('exactly one current enrolment', implode(' ', $result->rows[0]['blocking']));
    }

    public function test_ambiguous_destination_requires_manual_selection(): void
    {
        [$tenant, , $sourceSession, $term, $arm, $student] = $this->promotionFixture();
        $targetSession = $this->sessionFixture($tenant, '2026/2027');
        $this->termFixture($tenant, $targetSession, 'First Term');
        $this->classArmFixture($tenant, 'Primary 2', 2, 'B');
        $this->classArmFixture($tenant, 'Primary 2 Duplicate', 2, 'C');
        $this->summaryFixture($tenant, $student, $arm, $term, 'promoted');

        $result = $this->service->previewRollover($tenant->id, $sourceSession->id, $targetSession->id);

        $this->assertSame(1, $result->counts['blocked']);
        $this->assertStringContainsString('Multiple next class levels', implode(' ', $result->rows[0]['blocking']));
    }

    private function promotionFixture(bool $withEnrollment = true): array
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $sourceSession = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $sourceSession, 'Third Term', true);
        $arm = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        $student = $this->studentFixture($tenant, $arm);

        if ($withEnrollment) {
            $this->enrollmentFixture($tenant, $student, $arm, $sourceSession, $term);
        }

        return [$tenant, $actor, $sourceSession, $term, $arm, $student];
    }
}
