<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\MobileReportCardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileScoresAndResultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile score tests require sqlite :memory:.');
        }

        foreach ([
            'mobile_idempotency_keys', 'api_tokens', 'report_card_publications', 'termly_summaries', 'scores', 'assessment_types',
            'class_arm_subjects', 'students', 'subjects', 'class_arms', 'class_levels', 'terms',
            'academic_sessions', 'users', 'tenants',
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
            $table->string('staff_id')->nullable();
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
        Schema::create('mobile_idempotency_keys', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('user_id');
            $table->string('scope', 100); $table->uuid('request_id'); $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing'); $table->longText('response_json')->nullable();
            $table->timestamps(); $table->unique(['user_id', 'scope', 'request_id']);
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
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arm_subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('session_id');
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('assessment_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('term_id');
            $table->string('name');
            $table->float('weight_percentage');
            $table->float('objective_max')->nullable();
            $table->float('theory_max')->nullable();
            $table->boolean('is_exam')->default(false);
            $table->timestamps();
        });
        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('assessment_type_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->float('score')->nullable();
            $table->float('objective_score')->nullable();
            $table->float('theory_score')->nullable();
            $table->unsignedBigInteger('cbt_exam_id')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->string('score_source')->nullable();
            $table->string('source_reference_type')->nullable();
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->boolean('is_source_locked')->default(false);
            $table->timestamp('source_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id', 'subject_id', 'assessment_type_id', 'term_id']);
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
        });
    }

    public function test_teacher_receives_only_current_session_assignments_without_duplicates(): void
    {
        $school = $this->school();
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $oldSession = AcademicSession::create(['tenant_id' => $school['tenant']->id, 'name' => '2025/2026', 'is_current' => false]);

        foreach ([$school['session']->id, $school['session']->id, $oldSession->id] as $sessionId) {
            ClassArmSubject::create([
                'tenant_id' => $school['tenant']->id, 'class_arm_id' => $school['class']->id,
                'subject_id' => $school['subject']->id, 'teacher_id' => $teacher->id, 'session_id' => $sessionId,
            ]);
        }

        $this->withToken(ApiToken::issue($teacher, 'scores-test'))->getJson('/api/v1/scores/teaching')
            ->assertOk()->assertJsonPath('contract_version', 2)->assertJsonCount(1, 'assignments')
            ->assertJsonPath('assignments.0.subject_name', 'Biology');
    }

    public function test_score_save_validates_range_and_rejects_stale_version(): void
    {
        $school = $this->school();
        $admin = $this->user($school['tenant'], 'admin');
        $token = ApiToken::issue($admin, 'score-admin');
        $type = AssessmentType::create([
            'tenant_id' => $school['tenant']->id, 'term_id' => $school['term']->id,
            'name' => 'CA 1', 'weight_percentage' => 20, 'is_exam' => false,
        ]);

        $sheet = $this->withToken($token)->getJson("/api/v1/scores/sheet?class_arm_id={$school['class']->id}&subject_id={$school['subject']->id}")
            ->assertOk()->assertJsonPath('locked', false);
        $version = $sheet->json('version');
        $payload = [
            'class_arm_id' => $school['class']->id, 'subject_id' => $school['subject']->id,
            'term_id' => $school['term']->id, 'version' => $version,
            'request_id' => '0199322b-7cc8-73de-a9f1-3e34cb662dde',
            'scores' => [$school['student']->id => [$type->id => 21]],
        ];
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)->assertUnprocessable();

        $payload['scores'][$school['student']->id][$type->id] = 18;
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)
            ->assertOk()->assertJsonPath('saved', 1)->assertJsonPath('request_id', $payload['request_id']);
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)
            ->assertOk()->assertJsonPath('saved', 1)->assertJsonPath('request_id', $payload['request_id']);

        $payload['scores'][$school['student']->id][$type->id] = 17;
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)->assertUnprocessable();

        $payload['request_id'] = '1199322b-7cc8-73de-a9f1-3e34cb662dde';
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)
            ->assertConflict()->assertJsonPath('message', 'Scores changed on the server. Reload the sheet before saving your draft.');
    }

    public function test_published_and_source_controlled_scores_cannot_be_changed(): void
    {
        $school = $this->school();
        $admin = $this->user($school['tenant'], 'admin');
        $token = ApiToken::issue($admin, 'score-lock');
        $type = AssessmentType::create([
            'tenant_id' => $school['tenant']->id, 'term_id' => $school['term']->id,
            'name' => 'Exam', 'weight_percentage' => 60, 'is_exam' => true,
        ]);
        Score::create([
            'tenant_id' => $school['tenant']->id, 'student_id' => $school['student']->id,
            'subject_id' => $school['subject']->id, 'assessment_type_id' => $type->id,
            'term_id' => $school['term']->id, 'session_id' => $school['session']->id,
            'score' => 50, 'is_source_locked' => true, 'score_source' => 'cbt',
        ]);
        $sheet = $this->withToken($token)->getJson("/api/v1/scores/sheet?class_arm_id={$school['class']->id}&subject_id={$school['subject']->id}")
            ->assertOk()->assertJsonPath("students.0.scores.{$type->id}.locked", true);
        $payload = [
            'class_arm_id' => $school['class']->id, 'subject_id' => $school['subject']->id,
            'term_id' => $school['term']->id, 'version' => $sheet->json('version'),
            'request_id' => '0199322b-7cc8-73de-a9f1-3e34cb662dde',
            'scores' => [$school['student']->id => [$type->id => 40]],
        ];
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)->assertUnprocessable();

        ReportCardPublication::create([
            'tenant_id' => $school['tenant']->id, 'class_arm_id' => $school['class']->id,
            'term_id' => $school['term']->id, 'status' => 'published',
        ]);
        $fresh = $this->withToken($token)->getJson("/api/v1/scores/sheet?class_arm_id={$school['class']->id}&subject_id={$school['subject']->id}")
            ->assertOk()->assertJsonPath('locked', true);
        $payload['version'] = $fresh->json('version');
        $this->withToken($token)->postJson('/api/v1/scores/save', $payload)->assertStatus(423);
    }

    public function test_mobile_report_service_returns_only_published_summaries(): void
    {
        $school = $this->school();
        TermlySummary::create([
            'tenant_id' => $school['tenant']->id, 'student_id' => $school['student']->id,
            'class_arm_id' => $school['class']->id, 'term_id' => $school['term']->id,
            'session_id' => $school['session']->id, 'total_score' => 70, 'final_average' => 70,
            'subjects_offered' => 1, 'subjects_failed' => 0, 'computed_at' => now(),
        ]);

        $this->assertCount(0, app(MobileReportCardService::class)->forStudent($school['student']));
        ReportCardPublication::create([
            'tenant_id' => $school['tenant']->id, 'class_arm_id' => $school['class']->id,
            'term_id' => $school['term']->id, 'status' => 'published',
        ]);
        $this->assertCount(1, app(MobileReportCardService::class)->forStudent($school['student']));
    }

    private function school(): array
    {
        $tenant = Tenant::create(['name' => 'Greenfield Academy', 'slug' => 'greenfield-'.uniqid(), 'status' => 'active']);
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $term = Term::create(['tenant_id' => $tenant->id, 'session_id' => $session->id, 'name' => 'First Term', 'is_current' => true]);
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'SS 2', 'section' => 'senior']);
        $class = ClassArm::create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'A']);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO']);
        $student = Student::create([
            'tenant_id' => $tenant->id, 'current_class_arm_id' => $class->id, 'admission_number' => 'STU001',
            'first_name' => 'Amina', 'last_name' => 'Bello', 'status' => Student::STATUS_ACTIVE,
        ]);

        return compact('tenant', 'session', 'term', 'class', 'subject', 'student');
    }

    private function user(Tenant $tenant, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id, 'name' => str($role)->headline().' '.uniqid(), 'role' => $role,
            'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
