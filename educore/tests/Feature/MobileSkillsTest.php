<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileSkillsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile Skills tests require sqlite :memory:.');
        }

        foreach ([
            'student_skill_ratings', 'skill_definitions', 'students', 'class_arms', 'class_levels',
            'terms', 'academic_sessions', 'staff_permissions', 'api_tokens', 'users', 'tenants',
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
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('skill_definitions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('category');
            $table->string('name');
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'category', 'name']);
        });
        Schema::create('student_skill_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('skill_definition_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedTinyInteger('rating');
            $table->unsignedBigInteger('rated_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id', 'skill_definition_id', 'term_id'], 'skills_rating_unique');
        });
    }

    public function test_admin_workspace_and_sheet_are_tenant_scoped_and_expose_only_safe_student_fields(): void
    {
        [$tenant, $admin] = $this->school('Skills School', 'admin');
        [$foreignTenant] = $this->school('Foreign Skills School', 'admin');
        [$sessionId, $termId] = $this->academicPeriod($tenant->id, '2026/2027');
        [$foreignSessionId, $foreignTermId] = $this->academicPeriod($foreignTenant->id, 'Foreign Session');
        [$classId] = $this->classRoom($tenant->id, null, 'Year 12', 'A');
        [$foreignClassId] = $this->classRoom($foreignTenant->id, null, 'Year 12', 'B');
        $studentId = $this->student($tenant->id, $classId, 'STU001', 'Ada', 'Lovelace');
        $this->student($foreignTenant->id, $foreignClassId, 'FOREIGN001', 'Foreign', 'Student');

        $token = ApiToken::issue($admin, 'skills-admin');
        $index = $this->withToken($token)->getJson('/api/v1/skills')->assertOk();
        $index->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('capabilities.all_classes', true)
            ->assertJsonPath('metrics.classes', 1)
            ->assertJsonPath('metrics.students', 1)
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $classId)
            ->assertJsonMissing(['id' => $foreignClassId]);
        $this->assertGreaterThanOrEqual(10, (int) $index->json('metrics.skills'));

        $sheet = $this->withToken($token)
            ->getJson("/api/v1/skills/sheet?class_arm_id={$classId}&term_id={$termId}")
            ->assertOk()
            ->assertJsonPath('class.id', $classId)
            ->assertJsonPath('term.id', $termId)
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.id', $studentId)
            ->assertJsonPath('students.0.admission_number', 'STU001');

        $student = $sheet->json('students.0');
        $this->assertSame(['id', 'admission_number', 'name', 'ratings'], array_keys($student));
        $this->assertStringNotContainsString('Foreign', json_encode($sheet->json(), JSON_THROW_ON_ERROR));

        $this->withToken($token)
            ->getJson("/api/v1/skills/sheet?class_arm_id={$foreignClassId}&term_id={$termId}")
            ->assertUnprocessable();
        $this->withToken($token)
            ->getJson("/api/v1/skills/sheet?class_arm_id={$classId}&term_id={$foreignTermId}")
            ->assertUnprocessable();
    }

    public function test_form_teacher_sees_only_assigned_class_and_cannot_open_another_class(): void
    {
        [$tenant, $formTeacher] = $this->school('Form Teacher Skills School', 'form_teacher');
        [, $termId] = $this->academicPeriod($tenant->id, '2026/2027');
        [$ownClassId] = $this->classRoom($tenant->id, $formTeacher->id, 'Year 10', 'A');
        [$otherClassId] = $this->classRoom($tenant->id, null, 'Year 10', 'B');
        $this->student($tenant->id, $ownClassId, 'OWN001', 'Own', 'Student');
        $this->student($tenant->id, $otherClassId, 'OTHER001', 'Other', 'Student');

        $token = ApiToken::issue($formTeacher, 'skills-form-teacher');
        $this->withToken($token)
            ->getJson('/api/v1/skills')
            ->assertOk()
            ->assertJsonPath('capabilities.all_classes', false)
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $ownClassId);

        $this->withToken($token)
            ->getJson("/api/v1/skills/sheet?class_arm_id={$ownClassId}&term_id={$termId}")
            ->assertOk();
        $this->withToken($token)
            ->getJson("/api/v1/skills/sheet?class_arm_id={$otherClassId}&term_id={$termId}")
            ->assertForbidden();
    }

    public function test_skill_ratings_can_be_saved_updated_and_explicitly_cleared(): void
    {
        [$tenant, $admin] = $this->school('Skill Mutation School', 'admin');
        [$sessionId, $termId] = $this->academicPeriod($tenant->id, '2026/2027');
        [$classId] = $this->classRoom($tenant->id, null, 'Year 11', 'A');
        $studentId = $this->student($tenant->id, $classId, 'RATE001', 'Rated', 'Student');
        $token = ApiToken::issue($admin, 'skills-save');
        $workspace = $this->withToken($token)->getJson('/api/v1/skills')->assertOk();
        $skillId = (int) $workspace->json('skills.0.id');

        $this->withToken($token)->putJson('/api/v1/skills/sheet', [
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'ratings' => [[
                'student_id' => $studentId,
                'skills' => [['skill_id' => $skillId, 'rating' => 5]],
            ]],
        ])->assertOk()->assertJsonPath('saved', 1)->assertJsonPath('cleared', 0);

        $this->assertDatabaseHas('student_skill_ratings', [
            'tenant_id' => $tenant->id,
            'student_id' => $studentId,
            'skill_definition_id' => $skillId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'rating' => 5,
            'rated_by' => $admin->id,
        ]);

        $this->withToken($token)->putJson('/api/v1/skills/sheet', [
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'ratings' => [[
                'student_id' => $studentId,
                'skills' => [['skill_id' => $skillId, 'rating' => 0]],
            ]],
        ])->assertOk()->assertJsonPath('saved', 0)->assertJsonPath('cleared', 1);

        $this->assertDatabaseMissing('student_skill_ratings', [
            'tenant_id' => $tenant->id,
            'student_id' => $studentId,
            'skill_definition_id' => $skillId,
            'term_id' => $termId,
        ]);
    }

    public function test_save_rejects_student_or_skill_outside_the_selected_school_context(): void
    {
        [$tenant, $admin] = $this->school('Skill Boundary School', 'admin');
        [$foreignTenant] = $this->school('Foreign Skill Boundary School', 'admin');
        [, $termId] = $this->academicPeriod($tenant->id, '2026/2027');
        [$classId] = $this->classRoom($tenant->id, null, 'Year 9', 'A');
        [$foreignClassId] = $this->classRoom($foreignTenant->id, null, 'Year 9', 'B');
        $studentId = $this->student($tenant->id, $classId, 'SAFE001', 'Safe', 'Student');
        $foreignStudentId = $this->student($foreignTenant->id, $foreignClassId, 'FOREIGN002', 'Foreign', 'Student');
        $token = ApiToken::issue($admin, 'skills-boundary');
        $workspace = $this->withToken($token)->getJson('/api/v1/skills')->assertOk();
        $skillId = (int) $workspace->json('skills.0.id');
        $foreignSkillId = DB::table('skill_definitions')->insertGetId([
            'tenant_id' => $foreignTenant->id,
            'category' => 'affective',
            'name' => 'Foreign Skill',
            'order_index' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)->putJson('/api/v1/skills/sheet', [
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'ratings' => [[
                'student_id' => $foreignStudentId,
                'skills' => [['skill_id' => $skillId, 'rating' => 4]],
            ]],
        ])->assertUnprocessable();

        $this->withToken($token)->putJson('/api/v1/skills/sheet', [
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'ratings' => [[
                'student_id' => $studentId,
                'skills' => [['skill_id' => $foreignSkillId, 'rating' => 4]],
            ]],
        ])->assertUnprocessable();
    }

    public function test_subject_teacher_without_skills_permission_is_forbidden(): void
    {
        [, $teacher] = $this->school('Subject Teacher Skills School', 'subject_teacher');
        $token = ApiToken::issue($teacher, 'skills-denied');

        $this->withToken($token)->getJson('/api/v1/skills')->assertForbidden();
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
            'email' => str($name)->slug().'@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'staff_id' => strtoupper(substr(md5($name), 0, 8)),
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $user];
    }

    private function academicPeriod(int $tenantId, string $name): array
    {
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'is_current' => true,
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termId = DB::table('terms')->insertGetId([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'is_current' => true,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-18',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$sessionId, $termId];
    }

    private function classRoom(int $tenantId, ?int $formTutorId, string $levelName, string $armName): array
    {
        $levelId = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $levelName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $classId = DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenantId,
            'class_level_id' => $levelId,
            'form_tutor_id' => $formTutorId,
            'name' => $armName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$classId, $levelId];
    }

    private function student(int $tenantId, int $classId, string $admissionNumber, string $firstName, string $lastName): int
    {
        return DB::table('students')->insertGetId([
            'tenant_id' => $tenantId,
            'current_class_arm_id' => $classId,
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
