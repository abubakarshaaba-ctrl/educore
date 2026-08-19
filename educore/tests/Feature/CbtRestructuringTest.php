<?php

namespace Tests\Feature;

use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Cbt\CbtExamConfigurationService;
use App\Services\Cbt\CbtIntegrityService;
use App\Services\Cbt\CbtQuestionImportService;
use App\Services\Cbt\CbtQuestionNumberingService;
use App\Services\Cbt\CbtRetakeService;
use App\Services\Cbt\CbtSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CbtRestructuringTest extends TestCase
{
    use RefreshDatabase;

    private array $ids;
    private User $admin;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $now = now();
        $tenant = DB::table('tenants')->insertGetId(['name' => 'Test School', 'slug' => 'test-school', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $adminId = DB::table('users')->insertGetId(['tenant_id' => $tenant, 'name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $studentUser = DB::table('users')->insertGetId(['tenant_id' => $tenant, 'name' => 'Student', 'email' => 'student@example.test', 'password' => bcrypt('password'), 'role' => 'student', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $academicSession = DB::table('academic_sessions')->insertGetId(['tenant_id' => $tenant, 'name' => '2026/2027', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now]);
        $term = DB::table('terms')->insertGetId(['tenant_id' => $tenant, 'session_id' => $academicSession, 'name' => 'First Term', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now]);
        $level = DB::table('class_levels')->insertGetId(['tenant_id' => $tenant, 'name' => 'SS 3', 'section' => 'senior_secondary', 'order_index' => 12, 'created_at' => $now, 'updated_at' => $now]);
        $arm = DB::table('class_arms')->insertGetId(['tenant_id' => $tenant, 'class_level_id' => $level, 'name' => 'A', 'created_at' => $now, 'updated_at' => $now]);
        $subject = DB::table('subjects')->insertGetId(['tenant_id' => $tenant, 'name' => 'Biology', 'code' => 'BIO', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $student = DB::table('students')->insertGetId(['tenant_id' => $tenant, 'user_id' => $studentUser, 'current_class_arm_id' => $arm, 'admission_number' => 'ST001', 'first_name' => 'Ada', 'last_name' => 'Okafor', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $assessment = DB::table('assessment_types')->insertGetId(['tenant_id' => $tenant, 'term_id' => $term, 'name' => 'Examination', 'weight_percentage' => 70, 'is_exam' => true, 'created_at' => $now, 'updated_at' => $now]);
        $this->ids = compact('tenant', 'academicSession', 'term', 'level', 'arm', 'subject', 'assessment');
        $this->admin = User::findOrFail($adminId);
        $this->student = Student::findOrFail($student);
        $this->actingAs($this->admin);
    }

    public function test_dynamic_sections_and_mark_validation_work_for_more_than_two_sections(): void
    {
        [$exam, $bank] = $this->exam();
        foreach (['A', 'B', 'C', 'D'] as $index => $code) {
            $section = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => "Section {$code}", 'code' => $code, 'display_order' => $index + 1, 'section_type' => 'mixed', 'scoring_method' => 'mixed', 'answer_mode' => 'online', 'max_marks' => 10, 'is_required' => true, 'is_active' => true]);
            $question = $this->question($bank, "Question {$code}", 'essay', 10);
            $section->questions()->attach($question->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 10]);
        }
        $service = app(CbtExamConfigurationService::class);
        $service->recalculateExamTotals($exam);
        $this->assertSame([], $service->publicationErrors($exam->fresh()));
        $this->assertSame(4, $exam->fresh()->sections()->count());
        $this->assertSame(40.0, (float) $exam->fresh()->total_marks);

        $exam->sections()->first()->update(['max_marks' => 12]);
        $this->assertNotEmpty($service->publicationErrors($exam->fresh()));
    }

    public function test_hierarchical_numbering_uses_decimal_alpha_roman_and_upper_alpha_levels(): void
    {
        [, $bank] = $this->exam();
        $one = $this->question($bank, 'Root', 'essay', 0, null, true);
        $a = $this->question($bank, 'Child', 'essay', 0, $one, true);
        $i = $this->question($bank, 'Grandchild', 'essay', 0, $a, true);
        $upper = $this->question($bank, 'Great grandchild', 'essay', 1, $i);
        $rows = app(CbtQuestionNumberingService::class)->number(collect([$one, $a, $i, $upper]));
        $this->assertSame(['1', 'a', 'i', 'A'], $rows->pluck('display_number')->all());
        $this->assertFalse($one->countsForMarks());
    }

    public function test_pending_manual_section_is_not_zero_and_completed_80_of_100_syncs_to_56_of_70(): void
    {
        [$exam, $bank] = $this->exam();
        $autoSection = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Objective', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 80, 'is_required' => true, 'is_active' => true]);
        $manualSection = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Theory', 'code' => 'B', 'display_order' => 2, 'section_type' => 'theory', 'scoring_method' => 'manual', 'answer_mode' => 'online', 'max_marks' => 20, 'is_required' => true, 'is_active' => true]);
        $auto = $this->question($bank, 'Correct option?', 'mcq', 80); $auto->update(['option_a' => 'Yes', 'option_b' => 'No', 'correct_answer_letter' => 'a']);
        $manual = $this->question($bank, 'Explain.', 'essay', 20);
        $autoSection->questions()->attach($auto->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 80]);
        $manualSection->questions()->attach($manual->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 20]);
        $attempt = $this->attempt($exam, [$auto->id, $manual->id]);

        $submitted = app(CbtSubmissionService::class)->submit($attempt, [$auto->id => 'a'], [$manual->id => 'response']);
        $this->assertNull($submitted->raw_score);
        $this->assertNull($submitted->percentage);
        $this->assertDatabaseMissing('scores', ['cbt_exam_id' => $exam->id]);

        $graded = app(CbtSubmissionService::class)->grade($submitted, [$manual->id => 0], $this->admin->id);
        $this->assertSame(80.0, (float) $graded->raw_score);
        $this->assertDatabaseHas('scores', ['cbt_exam_id' => $exam->id, 'score' => 56, 'score_source' => 'cbt', 'is_source_locked' => 1]);
    }

    public function test_weighting_uses_the_configured_assessment_maximum_instead_of_hardcoding_seventy(): void
    {
        DB::table('assessment_types')->where('id', $this->ids['assessment'])->update(['weight_percentage' => 60]);
        [$exam, $bank] = $this->exam();
        $section = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'All', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 100, 'is_required' => true, 'is_active' => true]);
        $q1 = $this->question($bank, 'Eighty', 'mcq', 80); $q1->update(['option_a' => 'Correct', 'option_b' => 'Wrong', 'correct_answer_letter' => 'a']);
        $q2 = $this->question($bank, 'Twenty', 'mcq', 20); $q2->update(['option_a' => 'Correct', 'option_b' => 'Wrong', 'correct_answer_letter' => 'a']);
        foreach ([[$q1, 80], [$q2, 20]] as $i => [$q, $marks]) $section->questions()->attach($q->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => $i + 1, 'marks_override' => $marks]);
        app(CbtSubmissionService::class)->submit($this->attempt($exam, [$q1->id, $q2->id]), [$q1->id => 'a', $q2->id => 'b']);
        $this->assertDatabaseHas('scores', ['cbt_exam_id' => $exam->id, 'score' => 48]);
    }

    public function test_pending_optional_manual_section_is_excluded_instead_of_counted_as_zero(): void
    {
        [$exam, $bank] = $this->exam();
        $required = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Required', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 10, 'is_required' => true, 'is_active' => true]);
        $optional = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Optional', 'code' => 'B', 'display_order' => 2, 'section_type' => 'theory', 'scoring_method' => 'manual', 'answer_mode' => 'online', 'max_marks' => 5, 'is_required' => false, 'is_active' => true]);
        $auto = $this->question($bank, 'Required question', 'mcq', 10); $auto->update(['option_a' => 'Correct', 'option_b' => 'Wrong', 'correct_answer_letter' => 'a']);
        $manual = $this->question($bank, 'Optional explanation', 'essay', 5);
        $required->questions()->attach($auto->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 10]);
        $optional->questions()->attach($manual->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 5]);
        $result = app(CbtSubmissionService::class)->submit($this->attempt($exam, [$auto->id, $manual->id]), [$auto->id => 'a']);
        $this->assertSame(10.0, (float) $result->raw_score);
        $this->assertSame(10.0, (float) $result->maximum_score);
        $this->assertSame(100.0, (float) $result->percentage);
    }

    public function test_first_focus_loss_auto_submits_once_and_post_submit_answers_are_rejected(): void
    {
        [$exam, $bank] = $this->exam();
        $section = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'A', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 1, 'is_required' => true, 'is_active' => true]);
        $question = $this->question($bank, 'One?', 'mcq', 1); $question->update(['option_a' => 'Yes', 'option_b' => 'No', 'correct_answer_letter' => 'a']);
        $section->questions()->attach($question->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 1]);
        $attempt = $this->attempt($exam, [$question->id]);
        $uuid = '11111111-1111-4111-8111-111111111111';
        $first = app(CbtIntegrityService::class)->record($attempt, $uuid, 'visibility_hidden', [], [$question->id => 'a']);
        $second = app(CbtIntegrityService::class)->record($attempt->fresh(), $uuid, 'window_blur');
        $this->assertTrue($first['submitted']);
        $this->assertFalse($second['recorded']);
        $this->assertDatabaseCount('cbt_integrity_events', 3);
        $this->actingAs(User::where('role', 'student')->first());
        $this->postJson(route('cbt.session.autosave', $attempt), ['answers' => [$question->id => 'b']])->assertStatus(409);
    }

    public function test_retake_requires_admin_authorization_and_cross_tenant_authorization_is_denied(): void
    {
        [$exam] = $this->exam();
        CbtStudentSession::create(['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'student_id' => $this->student->id, 'attempt_number' => 1, 'is_authorized_attempt' => true, 'question_order' => [], 'answers' => [], 'essay_answers' => [], 'started_at' => now(), 'submitted_at' => now(), 'status' => 'graded', 'grading_completed_at' => now()]);
        $authorization = app(CbtRetakeService::class)->authorize($exam, $this->student, $this->admin, 'Connectivity interruption');
        $this->assertSame(2, $authorization->attempt_number);
        $otherTenant = DB::table('tenants')->insertGetId(['name' => 'Other', 'slug' => 'other', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $otherAdminId = DB::table('users')->insertGetId(['tenant_id' => $otherTenant, 'name' => 'Other Admin', 'email' => 'other@example.test', 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(HttpException::class);
        app(CbtRetakeService::class)->authorize($exam, $this->student, User::withoutGlobalScopes()->findOrFail($otherAdminId), 'Should be denied');
    }

    public function test_authorized_retake_creates_a_new_attempt_and_preserves_the_original(): void
    {
        [$exam, $bank] = $this->exam();
        $section = $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Objective', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 1, 'is_required' => true, 'is_active' => true]);
        $question = $this->question($bank, 'One?', 'mcq', 1); $question->update(['option_a' => 'Yes', 'option_b' => 'No', 'correct_answer_letter' => 'a']);
        $section->questions()->attach($question->id, ['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'display_order' => 1, 'marks_override' => 1]);
        $original = CbtStudentSession::create(['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'student_id' => $this->student->id, 'attempt_number' => 1, 'is_authorized_attempt' => true, 'question_order' => [$question->id], 'answers' => [$question->id => 'a'], 'essay_answers' => [], 'started_at' => now()->subHour(), 'submitted_at' => now(), 'status' => 'graded', 'raw_score' => 1, 'maximum_score' => 1, 'percentage' => 100, 'grading_completed_at' => now()]);
        app(CbtRetakeService::class)->authorize($exam, $this->student, $this->admin, 'Verified device failure');
        $exam->update(['status' => 'published']);

        $this->actingAs(User::findOrFail($this->student->user_id))
            ->post(route('cbt.exams.begin', $exam), ['integrity_acknowledged' => '1'])
            ->assertRedirect(route('cbt.exams.start', $exam));

        $this->assertDatabaseHas('cbt_student_sessions', ['id' => $original->id, 'attempt_number' => 1, 'status' => 'graded']);
        $this->assertDatabaseHas('cbt_student_sessions', ['cbt_exam_id' => $exam->id, 'student_id' => $this->student->id, 'attempt_number' => 2, 'status' => 'in_progress', 'is_authorized_attempt' => 1]);
    }

    public function test_subject_teacher_cannot_authorize_a_retake(): void
    {
        [$exam] = $this->exam();
        CbtStudentSession::create(['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'student_id' => $this->student->id, 'attempt_number' => 1, 'is_authorized_attempt' => true, 'question_order' => [], 'answers' => [], 'essay_answers' => [], 'started_at' => now(), 'submitted_at' => now(), 'status' => 'graded', 'grading_completed_at' => now()]);
        $teacher = User::create(['tenant_id' => $this->ids['tenant'], 'name' => 'Teacher', 'email' => 'teacher@example.test', 'password' => bcrypt('password'), 'role' => 'subject_teacher', 'is_active' => true]);
        $this->actingAs($teacher)->post(route('cbt.retakes.authorize', [$exam, $this->student]), ['reason' => 'Not permitted'])->assertForbidden();
        $this->assertDatabaseCount('cbt_retake_authorizations', 0);
    }

    public function test_hierarchical_import_is_previewed_and_committed_transactionally(): void
    {
        [, $bank] = $this->exam();
        $csv = implode("\n", [
            implode(',', CbtQuestionImportService::HEADERS),
            'B,Theory,Q1,,1,theory_group,"Read and answer",,,,,,0,manual,online,1,"Answer all parts",yes,,,1',
            'B,Theory,Q1a,Q1,2,short_answer,"State one point",,,,,,5,manual,online,2,,yes,"A point",,1',
        ]);
        $batch = app(CbtQuestionImportService::class)->preview($bank, UploadedFile::fake()->createWithContent('questions.csv', $csv), $this->admin->id);
        $this->assertSame('preview', $batch->status);
        $this->assertSame(2, app(CbtQuestionImportService::class)->import($batch));
        $parent = CbtQuestion::where('reference_code', 'Q1')->firstOrFail();
        $child = CbtQuestion::where('reference_code', 'Q1a')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_question_id);
        $this->assertSame(1, $child->level);
    }

    public function test_import_allows_the_same_question_number_in_different_sections(): void
    {
        [, $bank] = $this->exam();
        $csv = implode("\n", [
            implode(',', CbtQuestionImportService::HEADERS),
            'A,Objective,1,,1,single_choice,"First answer?",Yes,No,,,A,1,automatic,online,1,,yes,,,1',
            'B,Theory,1,,1,theory,"Explain the answer",,,,,,5,manual,online,1,,yes,"A clear explanation",,1',
        ]);
        $batch = app(CbtQuestionImportService::class)->preview($bank, UploadedFile::fake()->createWithContent('sections.csv', $csv), $this->admin->id);
        $this->assertSame('preview', $batch->status);
        $this->assertSame(2, app(CbtQuestionImportService::class)->import($batch));
        $this->assertSame(2, CbtQuestion::where('reference_code', '1')->count());
    }

    public function test_validated_import_can_attach_sections_directly_to_a_draft_exam(): void
    {
        [$exam, $bank] = $this->exam();
        $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Objective', 'code' => 'A', 'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic', 'answer_mode' => 'online', 'max_marks' => 1, 'is_required' => true, 'is_active' => true]);
        $exam->sections()->create(['tenant_id' => $this->ids['tenant'], 'name' => 'Theory', 'code' => 'B', 'display_order' => 2, 'section_type' => 'theory', 'scoring_method' => 'manual', 'answer_mode' => 'paper', 'max_marks' => 5, 'is_required' => true, 'is_active' => true]);
        $csv = implode("\n", [
            implode(',', CbtQuestionImportService::HEADERS),
            'A,Objective,1,,1,single_choice,"First answer?",Yes,No,,,A,1,automatic,online,1,,yes,,,1',
            'B,Theory,1,,1,theory,"Explain the answer",,,,,,5,manual,paper,1,,yes,"A clear explanation",,1',
        ]);
        $batch = app(CbtQuestionImportService::class)->preview($bank, UploadedFile::fake()->createWithContent('exam.csv', $csv), $this->admin->id, $exam);
        $this->assertSame('preview', $batch->status);
        $this->assertSame(2, app(CbtQuestionImportService::class)->import($batch));
        $this->assertSame(2, DB::table('cbt_exam_section_questions')->where('cbt_exam_id', $exam->id)->count());
        $this->assertSame([], app(CbtExamConfigurationService::class)->publicationErrors($exam->fresh()));
    }

    private function exam(): array
    {
        $bank = CbtQuestionBank::create(['tenant_id' => $this->ids['tenant'], 'subject_id' => $this->ids['subject'], 'class_level_id' => $this->ids['level'], 'name' => 'Bank', 'is_active' => true]);
        $exam = CbtExam::create(['tenant_id' => $this->ids['tenant'], 'question_bank_id' => $bank->id, 'term_id' => $this->ids['term'], 'class_arm_id' => $this->ids['arm'], 'assessment_type_id' => $this->ids['assessment'], 'title' => 'Biology Exam', 'duration_minutes' => 60, 'total_questions' => 0, 'total_marks' => 0, 'status' => 'draft', 'malpractice_enabled' => true, 'focus_loss_policy' => 'submit', 'max_focus_losses' => 0, 'retake_policy' => 'latest_valid_authorized_attempt', 'strict_marks_validation' => true]);
        return [$exam, $bank];
    }

    private function question(CbtQuestionBank $bank, string $text, string $type, float $marks, ?CbtQuestion $parent = null, bool $instruction = false): CbtQuestion
    {
        return CbtQuestion::create(['tenant_id' => $this->ids['tenant'], 'question_bank_id' => $bank->id, 'parent_question_id' => $parent?->id, 'level' => $parent ? $parent->level + 1 : 0, 'sequence' => 1, 'numbering_style' => 'auto', 'type' => $type, 'question_text' => $text, 'marks' => $marks, 'difficulty' => 1, 'is_instruction_only' => $instruction, 'requires_answer' => ! $instruction]);
    }

    private function attempt(CbtExam $exam, array $questionIds): CbtStudentSession
    {
        return CbtStudentSession::create(['tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id, 'student_id' => $this->student->id, 'attempt_number' => 1, 'is_authorized_attempt' => true, 'question_order' => $questionIds, 'answers' => [], 'essay_answers' => [], 'started_at' => now(), 'integrity_acknowledged_at' => now(), 'status' => 'in_progress']);
    }
}
