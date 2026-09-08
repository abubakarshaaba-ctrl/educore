<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class MobileAcademicCycleTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();

        if (!Schema::hasColumn('terms', 'next_term_begins')) {
            Schema::table('terms', fn (Blueprint $table) => $table->date('next_term_begins')->nullable());
        }
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 60);
            $table->string('type')->default('grant');
            $table->unsignedBigInteger('granted_by');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'module']);
        });
    }

    public function test_index_is_tenant_scoped_and_reports_current_context(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Cycle School']);
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);

        $foreign = $this->tenantFixture(['name' => 'Foreign Cycle School', 'slug' => 'foreign-cycle-'.uniqid()]);
        $foreignSession = $this->sessionFixture($foreign, '2099/2100', true);
        $this->termFixture($foreign, $foreignSession, 'Foreign Term', true);

        $this->withToken(ApiToken::issue($actor, 'cycle-index'))
            ->getJson('/api/v1/academic-cycle')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('current.session_id', $session->id)
            ->assertJsonPath('current.term_id', $term->id)
            ->assertJsonPath('metrics.sessions', 1)
            ->assertJsonPath('metrics.terms', 1)
            ->assertJsonCount(1, 'sessions')
            ->assertJsonCount(1, 'terms')
            ->assertJsonMissing(['name' => '2099/2100']);
    }

    public function test_session_creation_and_activation_use_shared_lifecycle_service_and_audit_log(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Session Lifecycle School']);
        $actor = $this->actorFixture($tenant);
        $old = $this->sessionFixture($tenant, '2025/2026', true);
        $token = ApiToken::issue($actor, 'cycle-session');

        $created = $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/sessions', [
                'name' => '2026/2027',
                'activate' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('session.current', false);
        $newId = $created->json('session.id');

        $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/sessions/'.$newId.'/activate')
            ->assertOk()
            ->assertJsonPath('session.current', true);

        $this->assertDatabaseHas('academic_sessions', ['id' => $old->id, 'is_current' => false]);
        $this->assertDatabaseHas('academic_sessions', ['id' => $newId, 'tenant_id' => $tenant->id, 'is_current' => true]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'academic_session.created']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'academic_session.activated']);
    }

    public function test_term_creation_can_activate_only_inside_current_session(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Term Lifecycle School']);
        $actor = $this->actorFixture($tenant);
        $currentSession = $this->sessionFixture($tenant, '2026/2027', true);
        $closedSession = $this->sessionFixture($tenant, '2025/2026', false);
        $token = ApiToken::issue($actor, 'cycle-term');

        $created = $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/terms', [
                'session_id' => $currentSession->id,
                'name' => 'First Term',
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-15',
                'next_term_begins' => '2027-01-11',
                'activate' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('term.current', true)
            ->assertJsonPath('term.next_term_begins', '2027-01-11');
        $termId = $created->json('term.id');
        $this->assertDatabaseHas('terms', ['id' => $termId, 'tenant_id' => $tenant->id, 'is_current' => true]);

        $closedTerm = $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/terms', [
                'session_id' => $closedSession->id,
                'name' => 'Historic Term',
                'start_date' => '2025-09-01',
                'end_date' => '2025-12-15',
                'activate' => false,
            ])->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/terms/'.$closedTerm->json('term.id').'/activate')
            ->assertUnprocessable();
    }

    public function test_term_readiness_exposes_open_cbt_blocker_and_close_is_refused(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Blocked Closure School']);
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $arm = $this->classArmFixture($tenant, 'Year 12', 12);
        $student = $this->studentFixture($tenant, $arm);

        $examId = DB::table('cbt_exams')->insertGetId([
            'tenant_id' => $tenant->id,
            'term_id' => $term->id,
            'class_arm_id' => $arm->id,
            'title' => 'Open CBT',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cbt_student_sessions')->insert([
            'tenant_id' => $tenant->id,
            'cbt_exam_id' => $examId,
            'student_id' => $student->id,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiToken::issue($actor, 'cycle-blocked');

        $this->withToken($token)
            ->getJson('/api/v1/academic-cycle/terms/'.$term->id.'/readiness')
            ->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonFragment(['1 CBT session(s) are still in progress for this term.']);

        $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/terms/'.$term->id.'/close')
            ->assertUnprocessable();
        $this->assertDatabaseHas('terms', ['id' => $term->id, 'is_current' => true]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'academic_term.closure_denied']);
    }

    public function test_ready_term_then_session_can_be_closed_in_sequence(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Ready Closure School']);
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $token = ApiToken::issue($actor, 'cycle-ready');

        $this->withToken($token)
            ->getJson('/api/v1/academic-cycle/terms/'.$term->id.'/readiness')
            ->assertOk()
            ->assertJsonPath('allowed', true);
        $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/terms/'.$term->id.'/close')
            ->assertOk()
            ->assertJsonPath('term.current', false);

        $this->withToken($token)
            ->getJson('/api/v1/academic-cycle/sessions/'.$session->id.'/readiness')
            ->assertOk()
            ->assertJsonPath('allowed', true);
        $this->withToken($token)
            ->postJson('/api/v1/academic-cycle/sessions/'.$session->id.'/close')
            ->assertOk()
            ->assertJsonPath('session.current', false);

        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'academic_term.closed']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'academic_session.closed']);
    }

    public function test_foreign_session_and_term_mutations_are_rejected(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Boundary Cycle School']);
        $actor = $this->actorFixture($tenant);
        $foreign = $this->tenantFixture(['name' => 'Foreign Boundary School', 'slug' => 'foreign-boundary-'.uniqid()]);
        $foreignSession = $this->sessionFixture($foreign, '2099/2100', true);
        $foreignTerm = $this->termFixture($foreign, $foreignSession, 'Foreign Term', true);
        $token = ApiToken::issue($actor, 'cycle-boundary');

        $this->withToken($token)->postJson('/api/v1/academic-cycle/sessions/'.$foreignSession->id.'/activate')->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/academic-cycle/sessions/'.$foreignSession->id.'/readiness')->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/academic-cycle/sessions/'.$foreignSession->id)->assertNotFound();
        $this->withToken($token)->postJson('/api/v1/academic-cycle/terms/'.$foreignTerm->id.'/activate')->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/academic-cycle/terms/'.$foreignTerm->id.'/readiness')->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/academic-cycle/terms/'.$foreignTerm->id)->assertNotFound();
    }

    public function test_custom_deny_blocks_academic_cycle_access(): void
    {
        $tenant = $this->tenantFixture(['name' => 'Denied Cycle School']);
        $actor = $this->actorFixture($tenant);
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'module' => 'academic-cycle',
            'type' => 'deny',
            'granted_by' => $actor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($actor, 'cycle-denied'))
            ->getJson('/api/v1/academic-cycle')
            ->assertForbidden();
    }
}
