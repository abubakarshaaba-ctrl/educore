<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileSubjectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile subjects tests require sqlite :memory:.');
        }

        foreach ([
            'student_subject_selections', 'class_level_subjects', 'class_arm_subjects', 'scores',
            'subjects', 'staff_permissions', 'api_tokens', 'users', 'tenants',
        ] as $table) {
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
            $table->string('staff_id')->nullable();
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
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('subject_id');
        });
        Schema::create('class_arm_subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('class_level_subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();
        });
    }

    public function test_subject_index_is_tenant_scoped_searchable_filterable_and_paginated(): void
    {
        [$tenant, $admin] = $this->school('Subject School');
        [$foreignTenant] = $this->school('Foreign Subject School');

        foreach (range(1, 12) as $index) {
            Subject::create([
                'tenant_id' => $tenant->id,
                'name' => $index === 12 ? 'Target Biology' : 'Subject '.$index,
                'code' => 'SUB'.$index,
                'is_active' => $index % 2 === 0,
            ]);
        }
        Subject::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Target Biology',
            'code' => 'FBIOL',
            'is_active' => true,
        ]);

        $token = ApiToken::issue($admin, 'subjects-index');
        $this->withToken($token)
            ->getJson('/api/v1/subjects?per_page=10&page=1')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('metrics.total', 12)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(10, 'subjects');

        $this->withToken($token)
            ->getJson('/api/v1/subjects?q=Target&status=active')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('subjects.0.name', 'Target Biology');
    }

    public function test_manager_can_create_update_and_delete_unused_subject(): void
    {
        [$tenant, $admin] = $this->school('Subject Mutation School');
        $token = ApiToken::issue($admin, 'subjects-mutation');

        $response = $this->withToken($token)
            ->postJson('/api/v1/subjects', [
                'name' => 'Biology',
                'code' => 'bio',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('subject.code', 'BIO')
            ->assertJsonPath('subject.active', true);

        $subjectId = (int) $response->json('subject.id');
        $this->withToken($token)
            ->patchJson('/api/v1/subjects/'.$subjectId, [
                'name' => 'Advanced Biology',
                'code' => 'ABIO',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('subject.name', 'Advanced Biology')
            ->assertJsonPath('subject.active', false);

        $this->withToken($token)
            ->deleteJson('/api/v1/subjects/'.$subjectId)
            ->assertOk();

        $this->assertSoftDeleted('subjects', [
            'id' => $subjectId,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_duplicate_tenant_name_or_code_is_rejected_but_other_tenant_is_independent(): void
    {
        [$tenant, $admin] = $this->school('Subject Duplicate School');
        [$foreignTenant] = $this->school('Foreign Duplicate School');
        Subject::create(['tenant_id' => $tenant->id, 'name' => 'Chemistry', 'code' => 'CHEM', 'is_active' => true]);
        Subject::create(['tenant_id' => $foreignTenant->id, 'name' => 'Physics', 'code' => 'PHY', 'is_active' => true]);
        $token = ApiToken::issue($admin, 'subjects-duplicate');

        $this->withToken($token)->postJson('/api/v1/subjects', [
            'name' => 'Chemistry', 'code' => 'CHEM2', 'is_active' => true,
        ])->assertUnprocessable();

        $this->withToken($token)->postJson('/api/v1/subjects', [
            'name' => 'Different Chemistry', 'code' => 'CHEM', 'is_active' => true,
        ])->assertUnprocessable();

        $this->withToken($token)->postJson('/api/v1/subjects', [
            'name' => 'Physics', 'code' => 'PHY', 'is_active' => true,
        ])->assertCreated();
    }

    public function test_referenced_subject_cannot_be_deleted_and_foreign_subject_cannot_be_mutated(): void
    {
        [$tenant, $admin] = $this->school('Subject Boundary School');
        [$foreignTenant] = $this->school('Foreign Subject Boundary School');
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $foreign = Subject::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign Mathematics', 'code' => 'FMATH', 'is_active' => true]);
        DB::table('class_level_subjects')->insert([
            'tenant_id' => $tenant->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiToken::issue($admin, 'subjects-boundary');

        $this->withToken($token)
            ->deleteJson('/api/v1/subjects/'.$subject->id)
            ->assertUnprocessable();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'deleted_at' => null]);

        $this->withToken($token)->patchJson('/api/v1/subjects/'.$foreign->id, [
            'name' => 'Tampered', 'code' => 'TMP', 'is_active' => false,
        ])->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/subjects/'.$foreign->id)->assertNotFound();
    }

    public function test_custom_deny_blocks_subject_read_and_write_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Subject School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'subjects',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiToken::issue($admin, 'subjects-denied');

        $this->withToken($token)->getJson('/api/v1/subjects')->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/subjects', [
            'name' => 'Denied Subject', 'code' => 'DEN', 'is_active' => true,
        ])->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'staff_id' => strtoupper(substr(md5($name), 0, 8)),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin];
    }
}
