<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use App\Services\TenantOnboardingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant onboarding tests require the isolated sqlite :memory: test database.');
        }

        config([
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
            'mail.default' => 'array',
        ]);

        $this->rebuildSchema();
    }

    public function test_incomplete_tenant_reports_blocking_readiness_items(): void
    {
        $tenant = $this->tenantFixture(['phone' => '08000000000', 'address' => '1 School Road']);
        $this->adminFixture($tenant);

        $status = $this->service()->status($tenant);

        $this->assertFalse($status->complete);
        $this->assertFalse($status->can_activate);
        $this->assertContains('School identity', $status->completed_items);
        $this->assertContains('Primary administrator', $status->completed_items);
        $this->assertTrue(collect($status->blocking_items)->contains(fn ($item) => str_contains($item, 'current academic session')));
        $this->assertTrue(collect($status->blocking_items)->contains(fn ($item) => str_contains($item, 'class level')));
        $this->assertTrue(collect($status->blocking_items)->contains(fn ($item) => str_contains($item, 'active subject')));
        $this->assertSame('tenant.onboarding.session', $status->next_step);
    }

    public function test_complete_active_tenant_can_activate_and_access_operations(): void
    {
        $tenant = $this->tenantFixture([
            'phone' => '08000000000',
            'address' => '1 School Road',
            'motto' => 'Excellence',
            'logo_path' => 'logos/school.png',
        ]);
        $this->adminFixture($tenant);
        $this->makeAcademicallyReady($tenant);
        $this->makeSettingsReady($tenant);

        $status = $this->service()->status($tenant);

        $this->assertTrue($status->complete);
        $this->assertTrue($status->can_activate);
        $this->assertTrue($status->can_access_operations);
        $this->assertSame([], $status->blocking_items);
        $this->assertGreaterThanOrEqual(80, $status->progress_percentage);
    }

    public function test_conflicting_current_sessions_and_terms_are_blocking(): void
    {
        $tenant = $this->tenantFixture();
        $this->adminFixture($tenant);

        AcademicSession::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => '2025/2026', 'is_current' => true]);
        AcademicSession::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        Term::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'session_id' => null, 'name' => 'First', 'start_date' => now()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'is_current' => true]);
        Term::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'session_id' => null, 'name' => 'Second', 'start_date' => now()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'is_current' => true]);

        $status = $this->service()->status($tenant);

        $this->assertTrue(collect($status->blocking_items)->contains('Only one academic session may be marked current.'));
        $this->assertTrue(collect($status->blocking_items)->contains('Only one term may be marked current.'));
    }

    public function test_urls_use_slug_local_subdomain_and_only_verified_custom_domain(): void
    {
        $unverified = $this->tenantFixture([
            'slug' => 'unverified-school',
            'custom_domain' => 'portal.local.test',
            'domain_verified' => false,
        ]);
        $verified = $this->tenantFixture([
            'slug' => 'verified-school',
            'custom_domain' => 'verified.local.test',
            'domain_verified' => true,
        ]);

        $unverifiedStatus = $this->service()->status($unverified);
        $verifiedStatus = $this->service()->status($verified);

        $this->assertStringContainsString('/school/unverified-school/login', $unverifiedStatus->urls['slug_login']);
        $this->assertSame('http://unverified-school.educore.test/login', $unverifiedStatus->urls['local_subdomain_login']);
        $this->assertNull($unverifiedStatus->urls['custom_domain']);
        $this->assertSame('http://verified.local.test/', $verifiedStatus->urls['custom_domain']);
    }

    public function test_mail_warning_is_non_blocking_and_does_not_expose_credentials(): void
    {
        $tenant = $this->tenantFixture();

        $status = $this->service()->status($tenant);

        $this->assertTrue(collect($status->warning_items)->contains(fn ($item) => str_contains($item, 'Mail transport')));
        $this->assertArrayHasKey('mail_transport', $status->environment);
        $this->assertFalse($status->environment['mail_transport']);
        $this->assertFalse(str_contains(json_encode($status->environment), 'password'));
    }

    private function service(): TenantOnboardingService
    {
        return app(TenantOnboardingService::class);
    }

    private function tenantFixture(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Bluerayy Academy',
            'slug' => 'bluerayy-academy',
            'email' => 'info@bluerayy.test',
            'phone' => null,
            'address' => null,
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent' => '#D79A21',
        ], $overrides));
    }

    private function adminFixture(Tenant $tenant, array $overrides = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'School Admin',
            'email' => 'admin'.$tenant->id.'@school.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ], $overrides));
    }

    private function makeAcademicallyReady(Tenant $tenant): void
    {
        $session = AcademicSession::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        Term::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_current' => true,
        ]);
        $level = ClassLevel::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'JSS 1', 'section' => 'junior', 'order_index' => 1]);
        ClassArm::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'A']);
        Subject::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
        GradingSystem::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'grade_letter' => 'A', 'min_score' => 70, 'max_score' => 100, 'remark' => 'Excellent']);
    }

    private function makeSettingsReady(Tenant $tenant): void
    {
        SchoolSetting::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'key' => 'currency', 'value' => 'NGN', 'group' => 'finance']);
        \App\Models\AdmissionPortalSetting::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'is_open' => false,
            'application_fee' => 0,
            'welcome_message' => 'Admissions setup pending.',
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'grading_systems',
            'subjects',
            'class_arms',
            'class_levels',
            'terms',
            'academic_sessions',
            'admission_portal_settings',
            'school_settings',
            'tenant_subscriptions',
            'platform_settings',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTables();
        $this->createUserTable();
        $this->createReadinessTables();
    }

    private function createTenantTables(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->string('motto')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->string('theme_primary', 20)->nullable();
            $table->string('theme_accent', 20)->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createUserTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('employment_status', 40)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createReadinessTables(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('status')->default('active');
            $table->string('billing_cycle')->default('annual');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('admission_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->boolean('is_open')->default(false);
            $table->decimal('application_fee', 10, 2)->default(0);
            $table->text('welcome_message')->nullable();
            $table->boolean('notify_guardian_sms')->default(false);
            $table->boolean('notify_guardian_email')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('grade_letter');
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('remark')->nullable();
            $table->boolean('is_pass_grade')->default(true);
            $table->timestamps();
        });
    }
}
