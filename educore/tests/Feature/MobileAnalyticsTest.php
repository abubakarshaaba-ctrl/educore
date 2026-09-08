<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile analytics tests require sqlite :memory:.');
        }

        foreach (['invoices', 'class_arms', 'students', 'terms', 'academic_sessions', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
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
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('invoice_number');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_admin_receives_tenant_scoped_native_analytics(): void
    {
        [$tenant, $admin, $session, $term] = $this->school('Analytics School', 'admin');
        [$otherTenant, , $otherSession, $otherTerm] = $this->school('Other Analytics School', 'admin');

        $student1 = Student::create(['tenant_id' => $tenant->id, 'admission_number' => 'ANA-001', 'first_name' => 'Amina', 'last_name' => 'Bello']);
        Student::create(['tenant_id' => $tenant->id, 'admission_number' => 'ANA-002', 'first_name' => 'Musa', 'last_name' => 'Ali']);
        $foreignStudent = Student::create(['tenant_id' => $otherTenant->id, 'admission_number' => 'OTH-001', 'first_name' => 'Foreign', 'last_name' => 'Student']);

        ClassArm::create(['tenant_id' => $tenant->id, 'name' => 'A']);
        ClassArm::create(['tenant_id' => $otherTenant->id, 'name' => 'Z']);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'student_id' => $student1->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'invoice_number' => 'ANA-INV-001',
            'total_amount' => 100000,
            'amount_paid' => 40000,
            'status' => 'partially_paid',
        ]);
        Invoice::create([
            'tenant_id' => $otherTenant->id,
            'student_id' => $foreignStudent->id,
            'term_id' => $otherTerm->id,
            'session_id' => $otherSession->id,
            'invoice_number' => 'OTHER-INV',
            'total_amount' => 999999,
            'amount_paid' => 999999,
            'status' => 'paid',
        ]);

        $response = $this->withToken(ApiToken::issue($admin, 'mobile-analytics'))
            ->getJson('/api/v1/operations/analytics');

        $response
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('module.key', 'analytics')
            ->assertJsonPath('module.mobile_policy', 'read_only')
            ->assertJsonPath('metrics.0.key', 'students')
            ->assertJsonPath('metrics.0.value', '2')
            ->assertJsonPath('metrics.1.key', 'classes')
            ->assertJsonPath('metrics.1.value', '1')
            ->assertJsonPath('metrics.4.key', 'collection')
            ->assertJsonPath('metrics.4.value', '40.0%')
            ->assertJsonPath('sections.3.key', 'finance')
            ->assertJsonPath('sections.3.records.0.fields.0.value', 'NGN 100,000.00')
            ->assertJsonPath('sections.3.records.0.fields.1.value', 'NGN 40,000.00');
    }

    public function test_analytics_does_not_leak_finance_to_role_without_fee_access(): void
    {
        [, $vicePrincipal] = $this->school('Academic Leadership School', 'vice_principal');

        $response = $this->withToken(ApiToken::issue($vicePrincipal, 'mobile-analytics-no-fees'))
            ->getJson('/api/v1/operations/analytics')
            ->assertOk();

        $this->assertFalse(collect($response->json('metrics'))->contains(fn ($metric) => ($metric['key'] ?? null) === 'collection'));
        $this->assertFalse(collect($response->json('sections'))->contains(fn ($section) => ($section['key'] ?? null) === 'finance'));
    }

    public function test_teacher_without_analytics_permission_is_forbidden(): void
    {
        [$tenant] = $this->school('Teacher Analytics School', 'admin');
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($teacher, 'mobile-analytics-denied'))
            ->getJson('/api/v1/operations/analytics')
            ->assertForbidden();
    }

    private function school(string $name, string $role): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' User',
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);
        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);

        return [$tenant, $user, $session, $term];
    }
}
