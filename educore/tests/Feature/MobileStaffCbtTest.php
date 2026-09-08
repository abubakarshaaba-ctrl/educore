<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileStaffCbtTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;
    private int $termId;
    private int $levelId;
    private int $armId;
    private int $biologySubjectId;
    private int $chemistrySubjectId;
    private User $admin;
    private User $teacher;
    private CbtQuestionBank $biologyBank;
    private CbtQuestionBank $chemistryBank;
    private CbtExam $biologyExam;
    private CbtExam $chemistryExam;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        $this->tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Mobile CBT School',
            'slug' => 'mobile-cbt-school',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $adminId = DB::table('users')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'CBT Administrator',
            'email' => 'cbt.admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $teacherId = DB::table('users')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'Biology Teacher',
            'email' => 'biology.teacher@example.test',
            'password' => bcrypt('password'),
            'role' => 'subject_teacher',
            'is_active' => true,
            'employment_status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => '2026/2027',
            'is_current' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->termId = DB::table('terms')->insertGetId([
            'tenant_id' => $this->tenantId,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->levelId = DB::table('class_levels')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'SS 3',
            'section' => 'senior_secondary',
            'order_index' => 12,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->armId = DB::table('class_arms')->insertGetId([
            'tenant_id' => $this->tenantId,
            'class_level_id' => $this->levelId,
            'name' => 'A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->biologySubjectId = DB::table('subjects')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'Biology',
            'code' => 'BIO',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->chemistrySubjectId = DB::table('subjects')->insertGetId([
            'tenant_id' => $this->tenantId,
            'name' => 'Chemistry',
            'code' => 'CHEM',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('class_arm_subjects')->insert([
            'tenant_id' => $this->tenantId,
            'class_arm_id' => $this->armId,
            'subject_id' => $this->biologySubjectId,
            'teacher_id' => $teacherId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->biologyBank = CbtQuestionBank::create([
            'tenant_id' => $this->tenantId,
            'subject_id' => $this->biologySubjectId,
            'class_level_id' => $this->levelId,
            'name' => 'SS3 Biology Bank',
            'is_active' => true,
        ]);
        $this->chemistryBank = CbtQuestionBank::create([
            'tenant_id' => $this->tenantId,
            'subject_id' => $this->chemistrySubjectId,
            'class_level_id' => $this->levelId,
            'name' => 'SS3 Chemistry Bank',
            'is_active' => true,
        ]);

        CbtQuestion::create([
            'tenant_id' => $this->tenantId,
            'question_bank_id' => $this->biologyBank->id,
            'type' => 'mcq',
            'question_text' => 'Which organelle contains chlorophyll?',
            'option_a' => 'Chloroplast',
            'option_b' => 'Nucleus',
            'correct_answer_letter' => 'a',
            'marks' => 2,
            'level' => 0,
            'sequence' => 1,
            'numbering_style' => 'auto',
            'is_instruction_only' => false,
            'requires_answer' => true,
            'scoring_method' => 'automatic',
        ]);

        $this->biologyExam = CbtExam::create([
            'tenant_id' => $this->tenantId,
            'question_bank_id' => $this->biologyBank->id,
            'term_id' => $this->termId,
            'class_arm_id' => $this->armId,
            'title' => 'Biology Mock CBT',
            'duration_minutes' => 60,
            'total_questions' => 0,
            'total_marks' => 0,
            'status' => 'closed',
            'scheduled_start' => now()->subHours(2),
            'scheduled_end' => now()->subHour(),
        ]);
        $this->chemistryExam = CbtExam::create([
            'tenant_id' => $this->tenantId,
            'question_bank_id' => $this->chemistryBank->id,
            'term_id' => $this->termId,
            'class_arm_id' => $this->armId,
            'title' => 'Chemistry Mock CBT',
            'duration_minutes' => 60,
            'total_questions' => 0,
            'total_marks' => 0,
            'status' => 'draft',
        ]);

        $this->biologyExam->classArms()->syncWithoutDetaching([
            $this->armId => ['tenant_id' => $this->tenantId],
        ]);
        $this->chemistryExam->classArms()->syncWithoutDetaching([
            $this->armId => ['tenant_id' => $this->tenantId],
        ]);

        $this->admin = User::findOrFail($adminId);
        $this->teacher = User::findOrFail($teacherId);
    }

    public function test_subject_teacher_only_receives_cbt_exams_for_assigned_subjects(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-test');

        $this->withToken($token)
            ->getJson('/api/v1/staff/cbt/exams')
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonPath('capabilities.full_access', false)
            ->assertJsonPath('capabilities.create_exam', true)
            ->assertJsonCount(1, 'exams')
            ->assertJsonPath('exams.0.id', $this->biologyExam->id)
            ->assertJsonPath('exams.0.subject.name', 'Biology');
    }

    public function test_subject_teacher_receives_scoped_creation_options_and_can_create_draft(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-create-test');

        $this->withToken($token)
            ->getJson('/api/v1/staff/cbt/options')
            ->assertOk()
            ->assertJsonPath('contract_version', 1)
            ->assertJsonCount(1, 'banks')
            ->assertJsonPath('banks.0.id', $this->biologyBank->id)
            ->assertJsonPath('banks.0.subject.name', 'Biology')
            ->assertJsonPath('banks.0.question_count', 1)
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $this->armId)
            ->assertJsonPath('defaults.term_id', $this->termId);

        $start = now()->addHour()->startOfMinute();
        $end = now()->addHours(2)->startOfMinute();

        $response = $this->withToken($token)
            ->postJson('/api/v1/staff/cbt/exams', [
                'title' => 'Native Biology Examination',
                'question_bank_id' => $this->biologyBank->id,
                'class_arm_ids' => [$this->armId],
                'term_id' => $this->termId,
                'duration_minutes' => 45,
                'scheduled_start' => $start->toIso8601String(),
                'scheduled_end' => $end->toIso8601String(),
                'malpractice_enabled' => true,
                'focus_loss_policy' => 'submit',
                'max_focus_losses' => 0,
                'require_fullscreen' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('reused', false)
            ->assertJsonPath('section_count', 1)
            ->assertJsonPath('question_count', 1);

        $examId = (int) $response->json('exam_id');
        $this->assertDatabaseHas('cbt_exams', [
            'id' => $examId,
            'tenant_id' => $this->tenantId,
            'question_bank_id' => $this->biologyBank->id,
            'term_id' => $this->termId,
            'title' => 'Native Biology Examination',
            'status' => 'draft',
            'duration_minutes' => 45,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenantId,
            'actor_user_id' => $this->teacher->id,
            'auditable_type' => CbtExam::class,
            'auditable_id' => $examId,
            'action' => 'cbt.exam.created',
        ]);
    }

    public function test_subject_teacher_cannot_create_exam_for_unassigned_bank(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-create-test');

        $this->withToken($token)
            ->postJson('/api/v1/staff/cbt/exams', [
                'title' => 'Chemistry Examination',
                'question_bank_id' => $this->chemistryBank->id,
                'class_arm_ids' => [$this->armId],
                'term_id' => $this->termId,
                'duration_minutes' => 45,
                'scheduled_start' => now()->addHour()->toIso8601String(),
                'scheduled_end' => now()->addHours(2)->toIso8601String(),
            ])
            ->assertForbidden();
    }

    public function test_subject_teacher_cannot_open_an_unassigned_subject_exam(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-test');

        $this->withToken($token)
            ->getJson('/api/v1/staff/cbt/exams/'.$this->chemistryExam->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'You can only manage exams for subjects you teach.');
    }

    public function test_admin_receives_full_staff_cbt_capability(): void
    {
        $token = ApiToken::issue($this->admin, 'android-cbt-admin-test');

        $this->withToken($token)
            ->getJson('/api/v1/staff/cbt/exams')
            ->assertOk()
            ->assertJsonPath('capabilities.full_access', true)
            ->assertJsonPath('capabilities.create_exam', true)
            ->assertJsonPath('capabilities.publish_exam', true)
            ->assertJsonPath('capabilities.close_exam', true)
            ->assertJsonPath('capabilities.reschedule_exam', true)
            ->assertJsonCount(2, 'exams');
    }

    public function test_native_schedule_endpoint_reschedules_and_reopens_a_closed_exam(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-test');
        $start = now()->addHour()->startOfMinute();
        $end = now()->addHours(3)->startOfMinute();

        $this->withToken($token)
            ->patchJson('/api/v1/staff/cbt/exams/'.$this->biologyExam->id.'/schedule', [
                'scheduled_start' => $start->format('Y-m-d\TH:i:s'),
                'scheduled_end' => $end->format('Y-m-d\TH:i:s'),
                'duration_minutes' => 75,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Exam rescheduled and reopened.')
            ->assertJsonPath('exam.status', 'published')
            ->assertJsonPath('exam.duration_minutes', 75);

        $fresh = $this->biologyExam->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertSame(75, (int) $fresh->duration_minutes);
        $this->assertTrue($fresh->scheduled_start->equalTo($start));
        $this->assertTrue($fresh->scheduled_end->equalTo($end));
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenantId,
            'actor_user_id' => $this->teacher->id,
            'auditable_type' => CbtExam::class,
            'auditable_id' => $this->biologyExam->id,
            'action' => 'cbt.exam.rescheduled',
        ]);
    }

    public function test_old_reschedule_url_is_not_part_of_the_native_contract(): void
    {
        $token = ApiToken::issue($this->teacher, 'android-cbt-test');

        $this->withToken($token)
            ->postJson('/api/v1/staff/cbt/exams/'.$this->biologyExam->id.'/reschedule', [
                'scheduled_start' => now()->addHour()->toIso8601String(),
                'scheduled_end' => now()->addHours(2)->toIso8601String(),
                'duration_minutes' => 60,
            ])
            ->assertNotFound();
    }
}
