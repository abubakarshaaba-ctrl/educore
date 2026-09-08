<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportCardDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MobileReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile Reports tests require sqlite :memory:.');
        }

        foreach ([
            'audit_logs', 'report_card_publications', 'termly_summaries', 'students', 'class_arms',
            'class_levels', 'terms', 'academic_sessions', 'staff_permissions', 'api_tokens', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
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
            $table->string('status')->default(Student::STATUS_ACTIVE);
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
            $table->float('total_score')->default(0);
            $table->float('final_average')->default(0);
            $table->integer('position_in_class')->nullable();
            $table->float('class_highest_avg')->nullable();
            $table->float('class_lowest_avg')->nullable();
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
        Schema::create('report_card_publications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'class_arm_id', 'term_id']);
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

    public function test_report_workspace_is_tenant_scoped_and_returns_only_computed_summaries(): void
    {
        $local = $this->school('Local Reports', 'admin');
        $foreign = $this->school('Foreign Reports', 'admin');
        $localPeriod = $this->period($local['tenant']->id, true);
        $foreignPeriod = $this->period($foreign['tenant']->id, true);
        $localClass = $this->classArm($local['tenant']->id, 'Year 12', 'A');
        $foreignClass = $this->classArm($foreign['tenant']->id, 'Year 12', 'B');
        $student = $this->student($local['tenant']->id, $localClass, 'REP001', 'Ada', 'Student');
        $this->summary($local['tenant']->id, $student, $localClass, $localPeriod['term'], $localPeriod['session']);
        $this->student($foreign['tenant']->id, $foreignClass, 'FOREIGN', 'Foreign', 'Learner');

        $token = ApiToken::issue($local['user'], 'reports-admin');
        $response = $this->withToken($token)
            ->getJson('/api/v1/reports?class_arm_id='.$localClass.'&term_id='.$localPeriod['term'])
            ->assertOk()
            ->assertJsonPath('capabilities.view', true)
            ->assertJsonPath('capabilities.compute', true)
            ->assertJsonPath('capabilities.publish', true)
            ->assertJsonPath('summary.computed', 1)
            ->assertJsonPath('summary.active_students', 1)
            ->assertJsonPath('summary.missing', 0)
            ->assertJsonPath('students.0.student.admission_number', 'REP001');

        $this->assertCount(1, $response->json('options.class_arms'));
        $this->assertStringNotContainsString('Foreign', json_encode($response->json(), JSON_THROW_ON_ERROR));

        $this->withToken($token)
            ->getJson('/api/v1/reports?class_arm_id='.$foreignClass.'&term_id='.$localPeriod['term'])
            ->assertNotFound();
        $this->withToken($token)
            ->getJson('/api/v1/reports?class_arm_id='.$localClass.'&term_id='.$foreignPeriod['term'])
            ->assertNotFound();
    }

    public function test_form_teacher_without_full_reports_access_is_forbidden(): void
    {
        $school = $this->school('Tutor Reports', 'form_teacher');
        $token = ApiToken::issue($school['user'], 'reports-tutor');
        $this->withToken($token)->getJson('/api/v1/reports')->assertForbidden();
    }

    public function test_publish_requires_computed_summaries_and_rejects_foreign_selectors(): void
    {
        $local = $this->school('Publish Local', 'admin');
        $foreign = $this->school('Publish Foreign', 'admin');
        $localPeriod = $this->period($local['tenant']->id, true);
        $foreignPeriod = $this->period($foreign['tenant']->id, true);
        $localClass = $this->classArm($local['tenant']->id, 'Year 11', 'A');
        $foreignClass = $this->classArm($foreign['tenant']->id, 'Year 11', 'B');
        $token = ApiToken::issue($local['user'], 'reports-publisher');

        $this->withToken($token)->postJson('/api/v1/reports/publish', [
            'class_arm_id' => $localClass,
            'term_id' => $localPeriod['term'],
        ])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/v1/reports/publish', [
            'class_arm_id' => $foreignClass,
            'term_id' => $localPeriod['term'],
        ])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/v1/reports/publish', [
            'class_arm_id' => $localClass,
            'term_id' => $foreignPeriod['term'],
        ])->assertUnprocessable();
    }

    public function test_publish_and_unpublish_are_audited_and_preserve_tenant_boundary(): void
    {
        $school = $this->school('Lifecycle Reports', 'admin');
        $period = $this->period($school['tenant']->id, true);
        $classId = $this->classArm($school['tenant']->id, 'Year 10', 'A');
        $studentId = $this->student($school['tenant']->id, $classId, 'PUB001', 'Publish', 'Student');
        $this->summary($school['tenant']->id, $studentId, $classId, $period['term'], $period['session']);
        $token = ApiToken::issue($school['user'], 'reports-lifecycle');

        $this->withToken($token)->postJson('/api/v1/reports/publish', [
            'class_arm_id' => $classId,
            'term_id' => $period['term'],
            'note' => 'First-term results approved.',
        ])->assertOk()
            ->assertJsonPath('publication.status', 'published')
            ->assertJsonPath('publication.note', 'First-term results approved.');

        $this->assertDatabaseHas('report_card_publications', [
            'tenant_id' => $school['tenant']->id,
            'class_arm_id' => $classId,
            'term_id' => $period['term'],
            'status' => 'published',
            'published_by' => $school['user']->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $school['tenant']->id,
            'actor_user_id' => $school['user']->id,
            'action' => 'report_cards.published',
        ]);

        $this->withToken($token)->postJson('/api/v1/reports/compute', [
            'class_arm_id' => $classId,
            'term_id' => $period['term'],
        ])->assertStatus(423);

        $this->withToken($token)->postJson('/api/v1/reports/unpublish', [
            'class_arm_id' => $classId,
            'term_id' => $period['term'],
        ])->assertOk()->assertJsonPath('publication.status', 'draft');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $school['tenant']->id,
            'actor_user_id' => $school['user']->id,
            'action' => 'report_cards.unpublished',
        ]);
        $this->withToken($token)->postJson('/api/v1/reports/unpublish', [
            'class_arm_id' => $classId,
            'term_id' => $period['term'],
        ])->assertUnprocessable();
    }

    public function test_report_pdf_delegates_tenant_scoped_summary_to_shared_document_service(): void
    {
        $school = $this->school('PDF Reports', 'admin');
        $period = $this->period($school['tenant']->id, true);
        $historicalClass = $this->classArm($school['tenant']->id, 'Year 10', 'A');
        $currentClass = $this->classArm($school['tenant']->id, 'Year 11', 'B');
        $studentId = $this->student($school['tenant']->id, $currentClass, 'PDF001', 'Historic', 'Student');
        $summaryId = $this->summary(
            $school['tenant']->id,
            $studentId,
            $historicalClass,
            $period['term'],
            $period['session'],
        );

        $documents = Mockery::mock(ReportCardDocumentService::class);
        $documents->shouldReceive('download')
            ->once()
            ->with($school['tenant']->id, $summaryId)
            ->andReturn(response('PDF-CONTENT', 200, ['Content-Type' => 'application/pdf']));
        $this->app->instance(ReportCardDocumentService::class, $documents);

        $token = ApiToken::issue($school['user'], 'reports-pdf');
        $this->withToken($token)
            ->get('/api/v1/reports/'.$summaryId.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSeeText('PDF-CONTENT');

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'current_class_arm_id' => $currentClass,
        ]);
    }

    public function test_report_pdf_hides_foreign_summary_ids_before_document_rendering(): void
    {
        $local = $this->school('Local PDF', 'admin');
        $foreign = $this->school('Foreign PDF', 'admin');
        $foreignPeriod = $this->period($foreign['tenant']->id, true);
        $foreignClass = $this->classArm($foreign['tenant']->id, 'Year 12', 'A');
        $foreignStudent = $this->student($foreign['tenant']->id, $foreignClass, 'FPDF01', 'Foreign', 'Student');
        $foreignSummary = $this->summary(
            $foreign['tenant']->id,
            $foreignStudent,
            $foreignClass,
            $foreignPeriod['term'],
            $foreignPeriod['session'],
        );

        $documents = Mockery::mock(ReportCardDocumentService::class);
        $documents->shouldNotReceive('download');
        $this->app->instance(ReportCardDocumentService::class, $documents);

        $token = ApiToken::issue($local['user'], 'reports-pdf-foreign');
        $this->withToken($token)
            ->get('/api/v1/reports/'.$foreignSummary.'/pdf')
            ->assertNotFound();
    }

    private function school(string $name, string $role): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' User',
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        return compact('tenant', 'user');
    }

    private function period(int $tenantId, bool $current): array
    {
        $session = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => '2026/2027',
            'is_current' => $current,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $term = DB::table('terms')->insertGetId([
            'tenant_id' => $tenantId,
            'session_id' => $session,
            'name' => 'First Term',
            'is_current' => $current,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return compact('session', 'term');
    }

    private function classArm(int $tenantId, string $levelName, string $armName): int
    {
        $level = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $levelName,
            'order_index' => random_int(1, 20),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenantId,
            'class_level_id' => $level,
            'name' => $armName,
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
            'status' => Student::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function summary(int $tenantId, int $studentId, int $classId, int $termId, int $sessionId): int
    {
        return DB::table('termly_summaries')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'class_arm_id' => $classId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'total_score' => 380,
            'final_average' => 76,
            'position_in_class' => 1,
            'total_students_in_class' => 1,
            'subjects_offered' => 5,
            'subjects_failed' => 0,
            'promotion_status' => 'pending',
            'subject_breakdown' => json_encode([]),
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
