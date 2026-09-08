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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileRiskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile risk tests require sqlite :memory:.');
        }

        foreach ([
            'student_risk_flags', 'risk_threshold_configs', 'attendance_records', 'scores',
            'termly_summaries', 'invoices', 'students', 'terms', 'academic_sessions',
            'class_arms', 'class_levels', 'api_tokens', 'users', 'tenants',
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
        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id')->default(1);
            $table->unsignedBigInteger('assessment_type_id')->default(1);
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->date('attendance_date');
            $table->string('status')->default('present');
            $table->string('remark')->nullable();
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
            $table->unsignedInteger('subjects_offered')->default(0);
            $table->unsignedInteger('subjects_failed')->default(0);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('risk_threshold_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->decimal('academic_threshold', 5, 2)->default(45.00);
            $table->decimal('attendance_threshold', 5, 2)->default(75.00);
            $table->integer('subjects_failed_threshold')->default(2);
            $table->boolean('include_fee_risk')->default(true);
            $table->integer('academic_weight')->default(40);
            $table->integer('attendance_weight')->default(35);
            $table->integer('fee_weight')->default(25);
            $table->timestamps();
        });
        Schema::create('student_risk_flags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->tinyInteger('academic_risk')->default(0);
            $table->tinyInteger('attendance_risk')->default(0);
            $table->tinyInteger('fee_risk')->default(0);
            $table->tinyInteger('subjects_failed')->default(0);
            $table->tinyInteger('composite_risk')->default(0);
            $table->string('risk_level')->default('low');
            $table->json('flags')->nullable();
            $table->string('status')->default('open');
            $table->text('intervention_note')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'term_id']);
        });
    }

    public function test_index_is_tenant_scoped_and_filters_risk_level(): void
    {
        [$tenant, $admin, $classArm, , $term] = $this->school('Risk School', 'admin');
        [$otherTenant, , $otherClass, , $otherTerm] = $this->school('Foreign Risk School', 'admin');
        $student = $this->student($tenant->id, $classArm->id, 'RISK-001', 'Amina', 'Bello');
        $foreignStudent = $this->student($otherTenant->id, $otherClass->id, 'FOREIGN-001', 'Foreign', 'Student');
        $critical = $this->riskFlag($tenant->id, $student->id, $term->id, $classArm->id, 'critical', 84);
        $this->riskFlag($otherTenant->id, $foreignStudent->id, $otherTerm->id, $otherClass->id, 'critical', 99);

        $response = $this->withToken(ApiToken::issue($admin, 'risk-index'))
            ->getJson('/api/v1/risk?risk_level=critical&status=all');

        $response
            ->assertOk()
            ->assertJsonPath('module.key', 'risk')
            ->assertJsonPath('module.mobile_policy', 'native_manage')
            ->assertJsonPath('selected.term_id', $term->id)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.critical', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('flags.0.id', $critical);

        $this->assertFalse(collect($response->json('flags'))->contains(
            fn ($flag) => ($flag['student']['admission_number'] ?? null) === 'FOREIGN-001'
        ));
    }

    public function test_detail_returns_intervention_context_without_cross_tenant_data(): void
    {
        [$tenant, $admin, $classArm, $session, $term] = $this->school('Risk Detail School', 'admin');
        [$otherTenant, , $otherClass, $otherSession, $otherTerm] = $this->school('Other Detail School', 'admin');
        $student = $this->student($tenant->id, $classArm->id, 'DETAIL-001', 'Musa', 'Ibrahim');
        $foreignStudent = $this->student($otherTenant->id, $otherClass->id, 'DETAIL-X', 'Other', 'Child');
        $flagId = $this->riskFlag($tenant->id, $student->id, $term->id, $classArm->id, 'high', 63);

        DB::table('attendance_records')->insert([
            ['tenant_id' => $tenant->id, 'student_id' => $student->id, 'class_arm_id' => $classArm->id, 'term_id' => $term->id, 'attendance_date' => '2026-09-01', 'status' => 'present', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenant->id, 'student_id' => $student->id, 'class_arm_id' => $classArm->id, 'term_id' => $term->id, 'attendance_date' => '2026-09-02', 'status' => 'absent', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $otherTenant->id, 'student_id' => $foreignStudent->id, 'class_arm_id' => $otherClass->id, 'term_id' => $otherTerm->id, 'attendance_date' => '2026-09-01', 'status' => 'absent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('scores')->insert([
            ['tenant_id' => $tenant->id, 'student_id' => $student->id, 'subject_id' => 1, 'assessment_type_id' => 1, 'term_id' => $term->id, 'session_id' => $session->id, 'score' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenant->id, 'student_id' => $student->id, 'subject_id' => 2, 'assessment_type_id' => 1, 'term_id' => $term->id, 'session_id' => $session->id, 'score' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $otherTenant->id, 'student_id' => $foreignStudent->id, 'subject_id' => 1, 'assessment_type_id' => 1, 'term_id' => $otherTerm->id, 'session_id' => $otherSession->id, 'score' => 100, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('invoices')->insert([
            ['tenant_id' => $tenant->id, 'student_id' => $student->id, 'term_id' => $term->id, 'session_id' => $session->id, 'invoice_number' => 'RISK-INV-1', 'total_amount' => 100000, 'amount_paid' => 40000, 'status' => 'partially_paid', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $otherTenant->id, 'student_id' => $foreignStudent->id, 'term_id' => $otherTerm->id, 'session_id' => $otherSession->id, 'invoice_number' => 'RISK-INV-X', 'total_amount' => 999999, 'amount_paid' => 0, 'status' => 'unpaid', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withToken(ApiToken::issue($admin, 'risk-detail'))
            ->getJson('/api/v1/risk/'.$flagId)
            ->assertOk()
            ->assertJsonPath('flag.student.admission_number', 'DETAIL-001')
            ->assertJsonPath('context.attendance_total', 2)
            ->assertJsonPath('context.attendance_present', 1)
            ->assertJsonPath('context.attendance_rate', 50)
            ->assertJsonPath('context.score_records', 2)
            ->assertJsonPath('context.score_average', 50)
            ->assertJsonPath('context.outstanding_invoices', 1)
            ->assertJsonPath('context.outstanding_balance', 60000);
    }

    public function test_compute_creates_flag_from_existing_school_evidence(): void
    {
        [$tenant, $admin, $classArm, $session, $term] = $this->school('Computed Risk School', 'admin');
        $student = $this->student($tenant->id, $classArm->id, 'COMP-001', 'Zainab', 'Sani');

        DB::table('termly_summaries')->insert([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'class_arm_id' => $classArm->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'total_score' => 180,
            'final_average' => 20,
            'subjects_offered' => 8,
            'subjects_failed' => 4,
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'invoice_number' => 'COMP-INV-1',
            'total_amount' => 80000,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'risk-compute'))
            ->postJson('/api/v1/risk/compute', ['term_id' => $term->id])
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('processed', 1);

        $flag = DB::table('student_risk_flags')->where('student_id', $student->id)->first();
        $this->assertNotNull($flag);
        $this->assertSame('high', $flag->risk_level);
        $this->assertSame(65, (int) $flag->composite_risk);
        $labels = json_decode($flag->flags, true, flags: JSON_THROW_ON_ERROR);
        $this->assertContains('avg_critically_low', $labels);
        $this->assertContains('subjects_failed', $labels);
        $this->assertContains('no_attendance_recorded', $labels);
        $this->assertContains('fees_overdue', $labels);
    }

    public function test_acknowledge_and_resolve_follow_explicit_state_transitions(): void
    {
        [$tenant, $admin, $classArm, , $term] = $this->school('Intervention School', 'admin');
        $student = $this->student($tenant->id, $classArm->id, 'INT-001', 'Maryam', 'Usman');
        $flagId = $this->riskFlag($tenant->id, $student->id, $term->id, $classArm->id, 'medium', 41);
        $token = ApiToken::issue($admin, 'risk-actions');

        $this->withToken($token)
            ->postJson('/api/v1/risk/'.$flagId.'/acknowledge', ['intervention_note' => 'Form tutor contacted parent.'])
            ->assertOk()
            ->assertJsonPath('flag.status', 'acknowledged')
            ->assertJsonPath('flag.intervention_note', 'Form tutor contacted parent.');

        $this->withToken($token)
            ->postJson('/api/v1/risk/'.$flagId.'/acknowledge', [])
            ->assertStatus(409);

        $this->withToken($token)
            ->postJson('/api/v1/risk/'.$flagId.'/resolve', ['intervention_note' => 'Attendance and performance improved.'])
            ->assertOk()
            ->assertJsonPath('flag.status', 'resolved')
            ->assertJsonPath('flag.intervention_note', 'Attendance and performance improved.');

        $this->withToken($token)
            ->postJson('/api/v1/risk/'.$flagId.'/resolve', [])
            ->assertStatus(409);
    }

    public function test_config_requires_weights_to_sum_to_one_hundred(): void
    {
        [, $admin] = $this->school('Risk Config School', 'admin');
        $token = ApiToken::issue($admin, 'risk-config');
        $payload = [
            'academic_threshold' => 45,
            'attendance_threshold' => 80,
            'subjects_failed_threshold' => 2,
            'include_fee_risk' => true,
            'academic_weight' => 50,
            'attendance_weight' => 30,
            'fee_weight' => 10,
        ];

        $this->withToken($token)
            ->putJson('/api/v1/risk/config', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('weights');

        $payload['fee_weight'] = 20;
        $this->withToken($token)
            ->putJson('/api/v1/risk/config', $payload)
            ->assertOk()
            ->assertJsonPath('config.attendance_threshold', 80)
            ->assertJsonPath('config.academic_weight', 50)
            ->assertJsonPath('config.fee_weight', 20);
    }

    public function test_cross_tenant_term_and_flag_access_are_rejected(): void
    {
        [, $admin] = $this->school('Boundary Risk School', 'admin');
        [$foreignTenant, , $foreignClass, , $foreignTerm] = $this->school('Foreign Boundary Risk School', 'admin');
        $foreignStudent = $this->student($foreignTenant->id, $foreignClass->id, 'BOUND-X', 'Foreign', 'Learner');
        $foreignFlag = $this->riskFlag($foreignTenant->id, $foreignStudent->id, $foreignTerm->id, $foreignClass->id, 'high', 70);
        $token = ApiToken::issue($admin, 'risk-boundary');

        $this->withToken($token)
            ->getJson('/api/v1/risk?term_id='.$foreignTerm->id)
            ->assertUnprocessable();

        $this->withToken($token)
            ->getJson('/api/v1/risk/'.$foreignFlag)
            ->assertNotFound();

        $this->withToken($token)
            ->postJson('/api/v1/risk/'.$foreignFlag.'/resolve', [])
            ->assertNotFound();
    }

    public function test_teacher_without_risk_permission_is_forbidden(): void
    {
        [$tenant] = $this->school('Denied Risk School', 'admin');
        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Subject Teacher',
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $this->withToken(ApiToken::issue($teacher, 'risk-denied'))
            ->getJson('/api/v1/risk')
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
        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);

        return [$tenant, $user, $classArm, $session, $term];
    }

    private function student(int $tenantId, int $classArmId, string $admission, string $first, string $last): Student
    {
        return Student::create([
            'tenant_id' => $tenantId,
            'admission_number' => $admission,
            'first_name' => $first,
            'last_name' => $last,
            'current_class_arm_id' => $classArmId,
            'status' => Student::STATUS_ACTIVE,
        ]);
    }

    private function riskFlag(int $tenantId, int $studentId, int $termId, int $classArmId, string $level, int $composite): int
    {
        return (int) DB::table('student_risk_flags')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'term_id' => $termId,
            'class_arm_id' => $classArmId,
            'academic_risk' => $composite,
            'attendance_risk' => 20,
            'fee_risk' => 0,
            'subjects_failed' => 2,
            'composite_risk' => $composite,
            'risk_level' => $level,
            'flags' => json_encode(['avg_below_threshold', 'subjects_failed'], JSON_THROW_ON_ERROR),
            'status' => 'open',
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
