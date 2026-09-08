<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileExportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile export tests require sqlite :memory:.');
        }

        foreach (['termly_summaries', 'invoices', 'students', 'terms', 'academic_sessions', 'class_arms', 'class_levels', 'api_tokens', 'users', 'tenants'] as $table) {
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
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->string('name');
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
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
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
        Schema::create('termly_summaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->decimal('total_score', 10, 2)->default(0);
            $table->decimal('final_average', 5, 2)->default(0);
            $table->unsignedInteger('position_in_class')->nullable();
            $table->unsignedInteger('subjects_offered')->default(0);
            $table->unsignedInteger('subjects_failed')->default(0);
            $table->timestamps();
        });
    }

    public function test_export_options_are_tenant_scoped_and_capability_aware(): void
    {
        [$tenant, $admin, $classArm] = $this->school('Export School', 'admin');
        [$otherTenant, , $otherClass] = $this->school('Foreign Export School', 'admin');

        $response = $this->withToken(ApiToken::issue($admin, 'export-options'))
            ->getJson('/api/v1/operations/exports');

        $response
            ->assertOk()
            ->assertJsonPath('module.key', 'exports')
            ->assertJsonPath('module.mobile_policy', 'native_download')
            ->assertJsonPath('capabilities.students', true)
            ->assertJsonPath('capabilities.broadsheet', true)
            ->assertJsonPath('capabilities.fees', true)
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $classArm->id);

        $this->assertNotSame($otherTenant->id, $tenant->id);
        $this->assertFalse(collect($response->json('classes'))->contains(fn ($item) => ($item['id'] ?? null) === $otherClass->id));
    }

    public function test_academic_leader_does_not_receive_fee_export_without_fee_access(): void
    {
        [, $vicePrincipal] = $this->school('Leadership Export School', 'vice_principal');

        $this->withToken(ApiToken::issue($vicePrincipal, 'leadership-export-options'))
            ->getJson('/api/v1/operations/exports')
            ->assertOk()
            ->assertJsonPath('capabilities.students', true)
            ->assertJsonPath('capabilities.broadsheet', true)
            ->assertJsonPath('capabilities.fees', false)
            ->assertJsonCount(0, 'sessions');
    }

    public function test_student_csv_is_streamed_for_current_tenant_only(): void
    {
        [$tenant, $admin, $classArm] = $this->school('CSV School', 'admin');
        [$otherTenant, , $otherClass] = $this->school('Foreign CSV School', 'admin');

        Student::create([
            'tenant_id' => $tenant->id,
            'admission_number' => 'CSV-001',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'gender' => 'female',
            'current_class_arm_id' => $classArm->id,
            'status' => Student::STATUS_ACTIVE,
        ]);
        Student::create([
            'tenant_id' => $otherTenant->id,
            'admission_number' => 'FOREIGN-001',
            'first_name' => 'Foreign',
            'last_name' => 'Student',
            'current_class_arm_id' => $otherClass->id,
            'status' => Student::STATUS_ACTIVE,
        ]);

        $response = $this->withToken(ApiToken::issue($admin, 'student-csv'))
            ->get('/api/v1/operations/exports?action=download&type=students&class_arm_id='.$classArm->id);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('CSV-001', $csv);
        $this->assertStringNotContainsString('FOREIGN-001', $csv);
    }

    public function test_cross_tenant_export_selector_is_rejected(): void
    {
        [, $admin] = $this->school('Boundary Export School', 'admin');
        [, , $foreignClass] = $this->school('Other Boundary School', 'admin');

        $this->withToken(ApiToken::issue($admin, 'cross-tenant-export'))
            ->getJson('/api/v1/operations/exports?action=download&type=students&class_arm_id='.$foreignClass->id)
            ->assertUnprocessable();
    }

    public function test_user_without_exports_permission_is_forbidden(): void
    {
        [$tenant] = $this->school('Denied Export School', 'admin');
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($teacher, 'export-denied'))
            ->getJson('/api/v1/operations/exports')
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
        $level = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'Year 12',
            'order_index' => 12,
        ]);
        $classArm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);
        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);
        Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);

        return [$tenant, $user, $classArm, $session];
    }
}
