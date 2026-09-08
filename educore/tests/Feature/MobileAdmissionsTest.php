<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileAdmissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile admissions tests require sqlite :memory:.');
        }

        foreach (['admissions', 'students', 'class_arms', 'class_levels', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->string('email')->nullable();
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
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('application_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('applying_for_class_level_id')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_relationship')->default('parent');
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_address')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->date('application_date');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->date('decision_date')->nullable();
            $table->unsignedBigInteger('enrolled_as_student_id')->nullable();
            $table->date('interview_date')->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->text('interview_notes')->nullable();
            $table->boolean('offer_letter_sent')->default(false);
            $table->timestamp('offer_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_index_is_tenant_scoped_searchable_and_paginated(): void
    {
        [$tenant, $admin, $level] = $this->school('Admissions School', 'admin');
        [$foreignTenant, , $foreignLevel] = $this->school('Foreign Admissions School', 'admin');
        $this->application($tenant->id, $level->id, 'APP-2026-LOCAL1', 'Amina', 'Bello');
        $this->application($tenant->id, $level->id, 'APP-2026-LOCAL2', 'Musa', 'Ali', status: 'shortlisted');
        $this->application($foreignTenant->id, $foreignLevel->id, 'APP-2026-FOREIGN', 'Foreign', 'Applicant');

        $response = $this->withToken(ApiToken::issue($admin, 'admissions-index'))
            ->getJson('/api/v1/admissions?search=Amina&status=all&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('module.key', 'admissions')
            ->assertJsonPath('module.mobile_policy', 'native_manage')
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('stats.shortlisted', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'admissions')
            ->assertJsonPath('admissions.0.application_number', 'APP-2026-LOCAL1');

        $this->assertFalse(collect($response->json('admissions'))->contains(
            fn ($item) => ($item['application_number'] ?? null) === 'APP-2026-FOREIGN'
        ));
    }

    public function test_create_rejects_foreign_class_level_and_creates_local_application(): void
    {
        [$tenant, $admin, $level] = $this->school('Create Admissions School', 'admin');
        [, , $foreignLevel] = $this->school('Foreign Create School', 'admin');
        $token = ApiToken::issue($admin, 'admissions-create');
        $payload = [
            'first_name' => 'Zainab',
            'last_name' => 'Sani',
            'date_of_birth' => '2012-05-14',
            'gender' => 'female',
            'applying_for_class_level_id' => $foreignLevel->id,
            'guardian_name' => 'Aisha Sani',
            'guardian_phone' => '08000000000',
            'guardian_relationship' => 'Mother',
        ];

        $this->withToken($token)
            ->postJson('/api/v1/admissions', $payload)
            ->assertUnprocessable();

        $payload['applying_for_class_level_id'] = $level->id;
        $this->withToken($token)
            ->postJson('/api/v1/admissions', $payload)
            ->assertCreated()
            ->assertJsonPath('admission.first_name', 'Zainab')
            ->assertJsonPath('admission.status', 'pending')
            ->assertJsonPath('admission.class_level_id', $level->id);

        $this->assertDatabaseHas('admissions', [
            'tenant_id' => $tenant->id,
            'first_name' => 'Zainab',
            'last_name' => 'Sani',
            'status' => 'pending',
        ]);
    }

    public function test_shortlist_status_update_is_native_and_tenant_scoped(): void
    {
        [$tenant, $admin, $level] = $this->school('Review Admissions School', 'admin');
        [$foreignTenant, , $foreignLevel] = $this->school('Foreign Review School', 'admin');
        $local = $this->application($tenant->id, $level->id, 'APP-2026-REVIEW', 'Maryam', 'Usman');
        $foreign = $this->application($foreignTenant->id, $foreignLevel->id, 'APP-2026-XREVIEW', 'Other', 'Applicant');
        $token = ApiToken::issue($admin, 'admissions-review');

        $this->withToken($token)
            ->patchJson('/api/v1/admissions/'.$local->id.'/status', [
                'status' => 'shortlisted',
                'notes' => 'Invite for interview.',
            ])
            ->assertOk()
            ->assertJsonPath('admission.status', 'shortlisted')
            ->assertJsonPath('admission.notes', 'Invite for interview.');

        $this->assertDatabaseHas('admissions', [
            'id' => $local->id,
            'tenant_id' => $tenant->id,
            'status' => 'shortlisted',
            'reviewed_by' => $admin->id,
        ]);

        $this->withToken($token)
            ->patchJson('/api/v1/admissions/'.$foreign->id.'/status', ['status' => 'rejected'])
            ->assertNotFound();
    }

    public function test_admitted_transition_rejects_foreign_class_arm_before_enrolment(): void
    {
        [$tenant, $admin, $level] = $this->school('Boundary Admissions School', 'admin');
        [, , , $foreignArm] = $this->school('Foreign Arm School', 'admin');
        $application = $this->application($tenant->id, $level->id, 'APP-2026-BOUND', 'Ibrahim', 'Yusuf');

        $this->withToken(ApiToken::issue($admin, 'admissions-arm-boundary'))
            ->patchJson('/api/v1/admissions/'.$application->id.'/status', [
                'status' => 'admitted',
                'class_arm_id' => $foreignArm->id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('admissions', [
            'id' => $application->id,
            'status' => 'pending',
            'enrolled_as_student_id' => null,
        ]);
    }

    public function test_teacher_without_admissions_permission_is_forbidden(): void
    {
        [$tenant] = $this->school('Denied Admissions School', 'admin');
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($teacher, 'admissions-denied'))
            ->getJson('/api/v1/admissions')
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
            'name' => 'Year 7',
            'order_index' => 7,
        ]);
        $arm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => 'A',
        ]);

        return [$tenant, $user, $level, $arm];
    }

    private function application(
        int $tenantId,
        int $levelId,
        string $number,
        string $first,
        string $last,
        string $status = 'pending',
    ): Admission {
        return Admission::create([
            'tenant_id' => $tenantId,
            'application_number' => $number,
            'first_name' => $first,
            'last_name' => $last,
            'date_of_birth' => '2012-01-01',
            'gender' => 'female',
            'applying_for_class_level_id' => $levelId,
            'guardian_name' => $first.' Guardian',
            'guardian_phone' => '08000000001',
            'guardian_relationship' => 'Parent',
            'status' => $status,
            'application_date' => '2026-09-08',
        ]);
    }
}
