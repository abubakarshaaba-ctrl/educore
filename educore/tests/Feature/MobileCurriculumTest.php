<?php

namespace Tests\Feature;

use App\Models\AcademicTrack;
use App\Models\ApiToken;
use App\Models\ClassLevel;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileCurriculumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile curriculum tests require sqlite :memory:.');
        }

        foreach ([
            'student_subject_selections', 'class_arms', 'class_level_subjects', 'subjects',
            'class_levels', 'academic_tracks', 'staff_permissions', 'api_tokens', 'users', 'tenants',
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
        Schema::create('academic_tracks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name', 80);
            $table->string('slug', 90)->unique();
            $table->string('section')->default('general');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_level_subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_status')->default('compulsory');
            $table->string('elective_group')->nullable();
            $table->unsignedInteger('min_required')->nullable();
            $table->unsignedInteger('max_allowed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_index_exposes_system_and_school_tracks_but_not_foreign_tracks(): void
    {
        [$tenant, $admin] = $this->school('Curriculum School');
        [$foreignTenant] = $this->school('Foreign Curriculum School');
        $system = AcademicTrack::create(['tenant_id' => null, 'name' => 'Science', 'slug' => 'science-system', 'section' => 'senior', 'is_active' => true]);
        $schoolTrack = AcademicTrack::create(['tenant_id' => $tenant->id, 'name' => 'Technical', 'slug' => 'technical-'.$tenant->id, 'section' => 'senior', 'is_active' => true]);
        AcademicTrack::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign Track', 'slug' => 'foreign-'.$foreignTenant->id, 'section' => 'senior', 'is_active' => true]);
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'SS 1', 'section' => 'senior', 'order_index' => 1]);
        Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO', 'is_active' => true]);

        $response = $this->withToken(ApiToken::issue($admin, 'curriculum-index'))
            ->getJson('/api/v1/curriculum')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonCount(2, 'tracks')
            ->assertJsonPath('metrics.school_tracks', 1)
            ->assertJsonPath('class_levels.0.id', $level->id);

        $tracks = collect($response->json('tracks'));
        $this->assertTrue((bool) $tracks->firstWhere('id', $system->id)['system']);
        $this->assertFalse((bool) $tracks->firstWhere('id', $schoolTrack->id)['system']);
        $this->assertFalse($tracks->contains('name', 'Foreign Track'));
    }

    public function test_system_tracks_are_read_only_and_school_tracks_can_be_managed(): void
    {
        [$tenant, $admin] = $this->school('Track Management School');
        $system = AcademicTrack::create(['tenant_id' => null, 'name' => 'Humanities', 'slug' => 'humanities-system', 'section' => 'senior', 'is_active' => true]);
        $token = ApiToken::issue($admin, 'curriculum-track');

        $created = $this->withToken($token)->postJson('/api/v1/curriculum/tracks', [
            'name' => 'Business Studies',
            'section' => 'senior',
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('track.system', false);
        $trackId = $created->json('track.id');

        $this->withToken($token)->patchJson('/api/v1/curriculum/tracks/'.$trackId, [
            'name' => 'Business',
            'section' => 'senior',
            'is_active' => false,
        ])->assertOk()->assertJsonPath('track.active', false);

        $this->withToken($token)->patchJson('/api/v1/curriculum/tracks/'.$system->id, [
            'name' => 'Changed System', 'section' => 'senior', 'is_active' => false,
        ])->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/curriculum/tracks/'.$system->id)->assertNotFound();

        $this->withToken($token)->deleteJson('/api/v1/curriculum/tracks/'.$trackId)->assertOk();
        $this->assertDatabaseMissing('academic_tracks', ['id' => $trackId]);
    }

    public function test_referenced_school_track_cannot_be_deleted(): void
    {
        [$tenant, $admin] = $this->school('Referenced Track School');
        $track = AcademicTrack::create(['tenant_id' => $tenant->id, 'name' => 'Science', 'slug' => 'science-'.$tenant->id, 'section' => 'senior', 'is_active' => true]);
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'SS 2', 'section' => 'senior', 'order_index' => 2]);
        DB::table('class_arms')->insert([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'academic_track_id' => $track->id,
            'name' => 'A',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'curriculum-track-reference'))
            ->deleteJson('/api/v1/curriculum/tracks/'.$track->id)
            ->assertUnprocessable();
        $this->assertDatabaseHas('academic_tracks', ['id' => $track->id]);
    }

    public function test_rule_crud_is_tenant_scoped_and_prevents_duplicates_and_invalid_limits(): void
    {
        [$tenant, $admin] = $this->school('Rule School');
        [$foreignTenant] = $this->school('Foreign Rule School');
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'SS 1', 'section' => 'senior', 'order_index' => 1]);
        $foreignLevel = ClassLevel::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign SS 1', 'section' => 'senior', 'order_index' => 1]);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Chemistry', 'code' => 'CHEM', 'is_active' => true]);
        $foreignSubject = Subject::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign Chemistry', 'code' => 'FCHEM', 'is_active' => true]);
        $track = AcademicTrack::create(['tenant_id' => $tenant->id, 'name' => 'Science', 'slug' => 'science-'.$tenant->id, 'section' => 'senior', 'is_active' => true]);
        $foreignTrack = AcademicTrack::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign Science', 'slug' => 'fscience-'.$foreignTenant->id, 'section' => 'senior', 'is_active' => true]);
        $token = ApiToken::issue($admin, 'curriculum-rule');

        $created = $this->withToken($token)->postJson('/api/v1/curriculum/rules', [
            'class_level_id' => $level->id,
            'academic_track_id' => $track->id,
            'subject_id' => $subject->id,
            'subject_status' => 'elective',
            'elective_group' => 'Science Group A',
            'min_required' => 1,
            'max_allowed' => 2,
        ])->assertCreated()->assertJsonPath('rule.status', 'elective');
        $ruleId = $created->json('rule.id');

        $this->withToken($token)->postJson('/api/v1/curriculum/rules', [
            'class_level_id' => $level->id,
            'academic_track_id' => $track->id,
            'subject_id' => $subject->id,
            'subject_status' => 'optional',
        ])->assertUnprocessable();

        $this->withToken($token)->patchJson('/api/v1/curriculum/rules/'.$ruleId, [
            'subject_status' => 'elective',
            'min_required' => 3,
            'max_allowed' => 1,
            'is_active' => true,
        ])->assertUnprocessable();

        $this->withToken($token)->patchJson('/api/v1/curriculum/rules/'.$ruleId, [
            'subject_status' => 'compulsory',
            'elective_group' => null,
            'min_required' => null,
            'max_allowed' => null,
            'is_active' => true,
        ])->assertOk()->assertJsonPath('rule.status', 'compulsory');

        foreach ([
            ['class_level_id' => $foreignLevel->id, 'academic_track_id' => null, 'subject_id' => $subject->id],
            ['class_level_id' => $level->id, 'academic_track_id' => null, 'subject_id' => $foreignSubject->id],
            ['class_level_id' => $level->id, 'academic_track_id' => $foreignTrack->id, 'subject_id' => $subject->id],
        ] as $foreignSelection) {
            $this->withToken($token)->postJson('/api/v1/curriculum/rules', $foreignSelection + [
                'subject_status' => 'compulsory',
            ])->assertStatus(422);
        }

        $this->withToken($token)->deleteJson('/api/v1/curriculum/rules/'.$ruleId)->assertOk();
        $this->assertDatabaseMissing('class_level_subjects', ['id' => $ruleId]);
    }

    public function test_custom_deny_blocks_curriculum_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Curriculum School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'curriculum',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'curriculum-denied'))
            ->getJson('/api/v1/curriculum')
            ->assertForbidden();
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
