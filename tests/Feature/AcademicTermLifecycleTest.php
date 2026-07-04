<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Score;
use App\Models\Term;
use App\Services\AcademicCycleService;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class AcademicTermLifecycleTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private AcademicCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
        $this->service = app(AcademicCycleService::class);
    }

    public function test_term_creation_validates_date_range(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);

        $this->expectException(ValidationException::class);
        $this->service->createTerm($tenant->id, [
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-04-01',
            'end_date' => '2026-01-01',
        ], $actor);
    }

    public function test_term_activation_enforces_one_current_term(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $old = $this->termFixture($tenant, $session, 'First Term', true);
        $new = $this->termFixture($tenant, $session, 'Second Term', false);

        $this->service->activateTerm($tenant->id, $new->id, $actor);

        $this->assertFalse($old->fresh()->is_current);
        $this->assertTrue($new->fresh()->is_current);
        $this->assertSame(1, Term::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_current', true)->count());
    }

    public function test_term_in_non_current_session_cannot_be_activated(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', false);
        $term = $this->termFixture($tenant, $session, 'First Term', false);

        $this->expectException(ValidationException::class);
        $this->service->activateTerm($tenant->id, $term->id, $actor);
    }

    public function test_term_closure_preserves_results_and_attendance(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $arm = $this->classArmFixture($tenant, 'Primary 1', 1);
        $student = $this->studentFixture($tenant, $arm);
        $this->enrollmentFixture($tenant, $student, $arm, $session, $term);
        Score::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'term_id' => $term->id, 'session_id' => $session->id, 'score' => 80]);
        AttendanceRecord::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'class_arm_id' => $arm->id, 'term_id' => $term->id, 'attendance_date' => '2026-01-10', 'status' => 'present']);

        $this->service->closeTerm($tenant->id, $term->id, $actor);

        $this->assertFalse($term->fresh()->is_current);
        $this->assertSame(1, Score::withoutTenantScope()->count());
        $this->assertSame(1, AttendanceRecord::withoutTenantScope()->count());
    }

    public function test_duplicate_term_closure_is_prevented(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', false);

        $this->expectException(ValidationException::class);
        $this->service->closeTerm($tenant->id, $term->id, $actor);
    }
}
