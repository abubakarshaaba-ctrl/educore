<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Services\AcademicCycleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class AcademicSessionLifecycleTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private AcademicCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
        $this->service = app(AcademicCycleService::class);
    }

    public function test_tenant_can_create_valid_session(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);

        $session = $this->service->createSession($tenant->id, ['name' => '2026/2027'], $actor);

        $this->assertSame('2026/2027', $session->name);
        $this->assertFalse($session->is_current);
        $this->assertSame(1, AuditLog::where('action', 'academic_session.created')->count());
    }

    public function test_duplicate_session_within_tenant_is_rejected_but_other_tenant_allowed(): void
    {
        $tenantA = $this->tenantFixture();
        $tenantB = $this->tenantFixture(['slug' => 'second-school']);
        $actorA = $this->actorFixture($tenantA);
        $actorB = $this->actorFixture($tenantB);

        $this->service->createSession($tenantA->id, ['name' => '2026/2027'], $actorA);
        $this->service->createSession($tenantB->id, ['name' => '2026/2027'], $actorB);

        $this->expectException(ValidationException::class);
        $this->service->createSession($tenantA->id, ['name' => '2026/2027'], $actorA);
    }

    public function test_activation_enforces_one_current_session_atomically(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $old = $this->sessionFixture($tenant, '2025/2026', true);
        $new = $this->sessionFixture($tenant, '2026/2027', false);

        $this->service->activateSession($tenant->id, $new->id, $actor);

        $this->assertFalse($old->fresh()->is_current);
        $this->assertTrue($new->fresh()->is_current);
        $this->assertSame(1, AcademicSession::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_current', true)->count());
    }

    public function test_cross_tenant_session_activation_is_denied(): void
    {
        $tenantA = $this->tenantFixture();
        $tenantB = $this->tenantFixture(['slug' => 'other-school']);
        $actor = $this->actorFixture($tenantA);
        $foreignSession = $this->sessionFixture($tenantB, '2026/2027', false);

        $this->expectException(ModelNotFoundException::class);
        $this->service->activateSession($tenantA->id, $foreignSession->id, $actor);
    }

    public function test_session_closure_with_current_term_blocker_is_denied_and_history_preserved(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $this->termFixture($tenant, $session, 'First Term', true);

        try {
            $this->service->closeSession($tenant->id, $session->id, $actor);
        } catch (ValidationException) {
            $this->assertTrue($session->fresh()->is_current);
            $this->assertSame(1, AcademicSession::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
            $this->assertSame(1, AuditLog::where('action', 'academic_session.closure_denied')->count());
            return;
        }

        $this->fail('Session closure should have been denied.');
    }
}
