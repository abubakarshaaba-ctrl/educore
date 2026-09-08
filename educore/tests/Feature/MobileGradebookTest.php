<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileGradebookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile Gradebook tests require sqlite :memory:.');
        }

        foreach ([
            'audit_logs', 'termly_summaries', 'scores', 'assessment_types', 'subjects', 'students',
            'class_arms', 'class_levels', 'terms', 'academic_sessions', 'staff_permissions',
            'api_tokens', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
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
            $table->timestamps();
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('next_term_begins')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
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
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('assessment_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('term_id');
            $table->string('name');
            $table->decimal('weight_percentage', 5, 2)->default(0);
            $table->decimal('objective_max', 5, 2)->nullable();
            $table->decimal('theory_max', 5, 2)->nullable();
            $table->boolean('is_exam')->default(false);
            $table->timestamps();
        });
        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('assessment_type_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('termly_summaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('final_average', 5, 2)->default(0);
            $table->integer('position_in_class')->nullable();
            $table->decimal('class_highest_avg', 5, 2)->nullable();
            $table->decimal('class_lowest_avg', 5, 2)->nullable();
            $table->integer('total_students_in_class')->nullable();
            $table->integer('subjects_offered')->default(0);
            $table->integer('subjects_failed')->default(0);
            $table->json('subject_breakdown')->nullable();
            $table->string('promotion_status')->default('pending');
            $table->text('form_tutor_remark')->nullable();
            $table->text('principal_remark')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_gradebook_is_tenant_scoped_and_uses_assessment_weight_as_maximum(): void
    {
        [$tenant, $admin] = $this->school('Gradebook School', 'admin');
        [$foreignTenant] = $this->school('Foreign Gradebook School', 'admin');
        [$sessionId, $termId] = $this->period($tenant->id, true);
        [, $foreignTermId] = $this->period($foreignTenant->id, true);
        $classId = $this->classRoom($tenant->id, null, 'Year 12', 'A');
        $foreignClassId = $this->classRoom($foreignTenant->id, null, 'Year 12', 'B');
        $studentId = $this->student($tenant->id, $classId, 'GB001', 'Ada', 'Lovelace');
        $subjectId = $this->subject($tenant->id, 'Biology', 'BIO');
        $assessmentId = $this->assessment($tenant->id, $termId, 'Continuous Assessment', 40, false);
        $summaryId = $this->summary($tenant->id, $studentId, $classId, $termId, $sessionId, 76.5);
        $this->score($tenant->id, $studentId, $classId, $subjectId, $assessmentId, $termId, $sessionId, 32);
        $this->student($foreignTenant->id, $foreignClassId, 'FOREIGN01', 'Foreign', 'Student');

        $token = ApiToken::issue($admin, 'gradebook-admin');
        $response = $this->withToken($token)
            ->getJson("/api/v1/gradebook?class_arm_id={$classId}&term_id={$termId}")
            ->assertOk()
            ->assertJsonPath('capabilities.view', true)
            ->assertJsonPath('capabilities.edit_form_tutor_remark', true)
            ->assertJsonPath('capabilities.edit_principal_remark', true)
            ->assertJsonPath('capabilities.compute_reports', false)
            ->assertJsonPath('capabilities.publish_reports', false)
            ->assertJsonPath('assessment_types.0.max_score', 40)
            ->assertJsonPath('students.0.summary.id', $summaryId)
            ->assertJsonPath('students.0.subjects.0.total', 32);

        $this->assertCount(1, $response->json('options.class_arms'));
        $this->assertSame($classId, (int) $response->json('options.class_arms.0.id'));
        $this->assertStringNotContainsString('Foreign', json_encode($response->json(), JSON_THROW_ON_ERROR));

        $this->withToken($token)
            ->getJson("/api/v1/gradebook?class_arm_id={$foreignClassId}&term_id={$termId}")
            ->assertNotFound();
        $this->withToken($token)
            ->getJson("/api/v1/gradebook?class_arm_id={$classId}&term_id={$foreignTermId}")
            ->assertNotFound();
    }

    public function test_form_teacher_sees_only_assigned_class_and_can_edit_only_form_tutor_remark(): void
    {
        [$tenant, $teacher] = $this->school('Tutor Gradebook School', 'form_teacher');
        [$sessionId, $termId] = $this->period($tenant->id, true);
        $ownClassId = $this->classRoom($tenant->id, $teacher->id, 'Year 10', 'A');
        $otherClassId = $this->classRoom($tenant->id, null, 'Year 10', 'B');
        $studentId = $this->student($tenant->id, $ownClassId, 'TUTOR01', 'Tutor', 'Student');
        $summaryId = $this->summary($tenant->id, $studentId, $ownClassId, $termId, $sessionId, 64.5);

        $token = ApiToken::issue($teacher, 'gradebook-tutor');
        $this->withToken($token)
            ->getJson('/api/v1/gradebook')
            ->assertOk()
            ->assertJsonCount(1, 'options.class_arms')
            ->assertJsonPath('options.class_arms.0.id', $ownClassId)
            ->assertJsonPath('capabilities.edit_form_tutor_remark', true)
            ->assertJsonPath('capabilities.edit_principal_remark', false);

        $this->withToken($token)
            ->getJson("/api/v1/gradebook?class_arm_id={$otherClassId}&term_id={$termId}")
            ->assertNotFound();

        $this->withToken($token)->putJson('/api/v1/gradebook/remarks/'.$summaryId, [
            'field' => 'form_tutor_remark',
            'remark' => 'Shows steady academic progress.',
        ])->assertOk()->assertJsonPath('remark', 'Shows steady academic progress.');

        $this->assertDatabaseHas('termly_summaries', [
            'id' => $summaryId,
            'form_tutor_remark' => 'Shows steady academic progress.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_user_id' => $teacher->id,
            'auditable_id' => $summaryId,
            'action' => 'report.remark.updated',
        ]);

        $this->withToken($token)->putJson('/api/v1/gradebook/remarks/'.$summaryId, [
            'field' => 'principal_remark',
            'remark' => 'Not authorised.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('termly_summaries', [
            'id' => $summaryId,
            'principal_remark' => 'Not authorised.',
        ]);
    }

    public function test_leadership_can_edit_principal_remark_but_foreign_summary_id_is_not_disclosed(): void
    {
        [$tenant, $admin] = $this->school('Leadership Gradebook School', 'principal');
        [$foreignTenant] = $this->school('Foreign Leadership School', 'principal');
        [$sessionId, $termId] = $this->period($tenant->id, true);
        [$foreignSessionId, $foreignTermId] = $this->period($foreignTenant->id, true);
        $classId = $this->classRoom($tenant->id, null, 'Year 11', 'A');
        $foreignClassId = $this->classRoom($foreignTenant->id, null, 'Year 11', 'B');
        $studentId = $this->student($tenant->id, $classId, 'LEAD01', 'Local', 'Learner');
        $foreignStudentId = $this->student($foreignTenant->id, $foreignClassId, 'FLEAD01', 'Foreign', 'Learner');
        $summaryId = $this->summary($tenant->id, $studentId, $classId, $termId, $sessionId, 80);
        $foreignSummaryId = $this->summary($foreignTenant->id, $foreignStudentId, $foreignClassId, $foreignTermId, $foreignSessionId, 82);

        $token = ApiToken::issue($admin, 'gradebook-leadership');
        $this->withToken($token)->putJson('/api/v1/gradebook/remarks/'.$summaryId, [
            'field' => 'principal_remark',
            'remark' => 'Excellent progress. Keep it up.',
        ])->assertOk()->assertJsonPath('message', 'Principal remark saved.');

        $this->assertDatabaseHas('termly_summaries', [
            'id' => $summaryId,
            'principal_remark' => 'Excellent progress. Keep it up.',
        ]);

        $this->withToken($token)->putJson('/api/v1/gradebook/remarks/'.$foreignSummaryId, [
            'field' => 'principal_remark',
            'remark' => 'Tampered',
        ])->assertNotFound();

        $this->assertDatabaseMissing('termly_summaries', [
            'id' => $foreignSummaryId,
            'principal_remark' => 'Tampered',
        ]);
    }

    public function test_subject_teacher_without_gradebook_or_remark_authority_is_forbidden(): void
    {
        [, $teacher] = $this->school('Denied Gradebook School', 'subject_teacher');
        $token = ApiToken::issue($teacher, 'gradebook-denied');

        $this->withToken($token)->getJson('/api/v1/gradebook')->assertForbidden();
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
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $user];
    }

    private function period(int $tenantId, bool $current = false): array
    {
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => '2026/2027',
            'is_current' => $current,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termId = DB::table('terms')->insertGetId([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'start_date' => '2026-09-14',
            'end_date' => '2026-12-18',
            'is_current' => $current,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$sessionId, $termId];
    }

    private function classRoom(int $tenantId, ?int $formTutorId, string $levelName, string $arm): int
    {
        $levelId = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $levelName,
            'section' => 'secondary',
            'order_index' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenantId,
            'class_level_id' => $levelId,
            'form_tutor_id' => $formTutorId,
            'name' => $arm,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(int $tenantId, int $classId, string $admission, string $first, string $last): int
    {
        return DB::table('students')->insertGetId([
            'tenant_id' => $tenantId,
            'current_class_arm_id' => $classId,
            'admission_number' => $admission,
            'first_name' => $first,
            'last_name' => $last,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subject(int $tenantId, string $name, string $code): int
    {
        return DB::table('subjects')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assessment(int $tenantId, int $termId, string $name, float $weight, bool $exam): int
    {
        return DB::table('assessment_types')->insertGetId([
            'tenant_id' => $tenantId,
            'term_id' => $termId,
            'name' => $name,
            'weight_percentage' => $weight,
            'is_exam' => $exam,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function score(
        int $tenantId,
        int $studentId,
        int $classId,
        int $subjectId,
        int $assessmentId,
        int $termId,
        int $sessionId,
        float $score,
    ): void {
        DB::table('scores')->insert([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'class_arm_id' => $classId,
            'subject_id' => $subjectId,
            'assessment_type_id' => $assessmentId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'score' => $score,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function summary(
        int $tenantId,
        int $studentId,
        int $classId,
        int $termId,
        int $sessionId,
        float $average,
    ): int {
        return DB::table('termly_summaries')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'total_score' => $average,
            'final_average' => $average,
            'position_in_class' => 1,
            'total_students_in_class' => 1,
            'subjects_offered' => 1,
            'subjects_failed' => 0,
            'promotion_status' => 'pending',
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
