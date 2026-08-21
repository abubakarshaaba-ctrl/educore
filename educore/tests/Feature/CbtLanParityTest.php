<?php

namespace Tests\Feature;

use App\Http\Controllers\CbtLanController;
use App\Models\CbtExam;
use App\Models\CbtExamSection;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Cbt\CbtLanAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CbtLanParityTest extends TestCase
{
    use RefreshDatabase;

    private array $ids;
    private User $admin;
    private User $studentUser;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $now = now();
        $tenant = DB::table('tenants')->insertGetId([
            'name' => 'LAN Test School', 'slug' => 'lan-test-school', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $adminId = DB::table('users')->insertGetId([
            'tenant_id' => $tenant, 'name' => 'LAN Admin', 'email' => 'lan.admin@example.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentUserId = DB::table('users')->insertGetId([
            'tenant_id' => $tenant, 'name' => 'LAN Student', 'email' => 'lan.student@example.test',
            'password' => bcrypt('password'), 'role' => 'student', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $academicSession = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenant, 'name' => '2026/2027', 'is_current' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $term = DB::table('terms')->insertGetId([
            'tenant_id' => $tenant, 'session_id' => $academicSession, 'name' => 'First Term',
            'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'is_current' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $level = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenant, 'name' => 'SS 3', 'section' => 'senior_secondary',
            'order_index' => 12, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $arm = DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenant, 'class_level_id' => $level, 'name' => 'A',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $subject = DB::table('subjects')->insertGetId([
            'tenant_id' => $tenant, 'name' => 'Biology', 'code' => 'BIO', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentId = DB::table('students')->insertGetId([
            'tenant_id' => $tenant, 'user_id' => $studentUserId, 'current_class_arm_id' => $arm,
            'admission_number' => 'LAN001', 'first_name' => 'Ada', 'last_name' => 'Okafor',
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $assessment = DB::table('assessment_types')->insertGetId([
            'tenant_id' => $tenant, 'term_id' => $term, 'name' => 'Examination',
            'weight_percentage' => 70, 'is_exam' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->ids = compact('tenant', 'academicSession', 'term', 'level', 'arm', 'subject', 'assessment');
        $this->admin = User::findOrFail($adminId);
        $this->studentUser = User::findOrFail($studentUserId);
        $this->student = Student::findOrFail($studentId);
        $this->actingAs($this->admin);
    }

    public function test_export_contains_current_release_token_exam_snapshot_and_history(): void
    {
        [$exam, $section, $question] = $this->exam();
        $session = $this->attempt($exam, 'graded', now(), now());
        DB::table('cbt_integrity_events')->insert([
            'tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id,
            'cbt_student_session_id' => $session->id, 'student_id' => $this->student->id,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'fullscreen_exit',
            'severity' => 'critical', 'metadata' => json_encode(['source' => 'test']),
            'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cbt_section_attempts')->insert([
            'tenant_id' => $this->ids['tenant'], 'cbt_student_session_id' => $session->id,
            'cbt_exam_section_id' => $section->id, 'raw_score' => 1, 'maximum_score' => 1,
            'status' => 'graded', 'scored_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cbt_question_scores')->insert([
            'tenant_id' => $this->ids['tenant'], 'cbt_student_session_id' => $session->id,
            'cbt_exam_section_id' => $section->id, 'cbt_question_id' => $question->id,
            'score' => 1, 'maximum_score' => 1, 'scoring_method' => 'automatic',
            'status' => 'graded', 'scored_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->get(route('cbt.lan.export', $exam));
        $response->assertOk();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(CbtLanController::PACKAGE_VERSION, $payload['package_version']);
        $this->assertSame(CbtLanController::APPLICATION_RELEASE, $payload['required_lan_release']);
        $this->assertNotEmpty($payload['sync_token']);
        $this->assertSame($payload['sync_token'], $payload['tables']['cbt_exams'][0]['lan_sync_token']);
        $this->assertCount(1, $payload['history']['sessions']);
        $this->assertCount(1, $payload['history']['section_attempts']);
        $this->assertCount(1, $payload['history']['question_scores']);
        $this->assertCount(1, $payload['history']['integrity_events']);

        $package = UploadedFile::fake()->createWithContent(
            'current-lan-package.json',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
        $this->withServerVariables([
            'HTTP_HOST' => '192.168.50.1',
            'SERVER_NAME' => '192.168.50.1',
        ]);
        $this->post('http://192.168.50.1/cbt/lan/import', ['package' => $package])
            ->assertSessionHas('success');
        Storage::disk('local')->assertExists('cbt-lan/installation.json');
    }

    public function test_student_uses_only_admission_number_on_private_lan_and_is_restricted_to_cbt(): void
    {
        [$exam] = $this->exam();
        [$otherExam] = $this->exam();
        $otherExam->update(['title' => 'Unimported Mathematics Examination']);
        $otherExam->refresh();
        app(CbtLanAccessService::class)->activateImportedPackage([
            'exam_id' => $exam->id,
            'tenant_id' => $this->ids['tenant'],
        ]);
        Auth::logout();
        $this->withServerVariables([
            'HTTP_HOST' => '192.168.50.1',
            'SERVER_NAME' => '192.168.50.1',
        ]);

        $this->get('http://192.168.50.1/cbt/lan/access')->assertOk()
            ->assertSee('Enter your admission number')
            ->assertSee('No password required')
            ->assertDontSee('name="password"', false);

        $this->post('http://192.168.50.1/cbt/lan/access', [
            'admission_number' => strtolower($this->student->admission_number),
        ])->assertRedirect(route('cbt.exams.start', $exam));

        $this->assertAuthenticatedAs($this->studentUser);
        $this->assertTrue((bool) session('cbt_lan_only'));
        $this->assertSame([$exam->id], session('cbt_lan_exam_ids'));
        $this->get(route('student.portal.exams'))->assertOk()
            ->assertSee($exam->title)
            ->assertDontSee($otherExam->title);
        $this->get(route('student.portal.results'))->assertRedirect(route('student.portal.exams'));
        $this->get(route('cbt.exams.start', $otherExam))->assertRedirect(route('student.portal.exams'));
        $this->get(route('cbt.exams.start', $exam))->assertOk()
            ->assertSee('CBT LAN')
            ->assertDontSee('My Results');
    }

    public function test_admission_number_access_is_not_exposed_on_the_cloud_host(): void
    {
        [$exam] = $this->exam();
        app(CbtLanAccessService::class)->activateImportedPackage([
            'exam_id' => $exam->id,
            'tenant_id' => $this->ids['tenant'],
        ]);
        Auth::logout();

        $this->withServerVariables([
            'HTTP_HOST' => 'educoreng.online',
            'SERVER_NAME' => 'educoreng.online',
            'HTTPS' => 'on',
        ])->get('/cbt/lan/access')->assertNotFound();
    }

    public function test_lan_import_can_provision_a_local_account_for_a_student_without_portal_access(): void
    {
        [$exam] = $this->exam();
        $studentId = DB::table('students')->insertGetId([
            'tenant_id' => $this->ids['tenant'], 'user_id' => null,
            'current_class_arm_id' => $this->ids['arm'], 'admission_number' => 'LAN-NO-ACCOUNT',
            'first_name' => 'Musa', 'last_name' => 'Bello', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        app(CbtLanAccessService::class)->provisionMissingStudentAccounts($exam->id);

        $student = Student::withoutTenantScope()->findOrFail($studentId);
        $this->assertNotNull($student->user_id);
        $this->assertGreaterThan(8_000_000_000_000_000_000, $student->user_id);
        $this->assertTrue(User::findOrFail($student->user_id)->isStudent());
    }

    public function test_lan_dashboard_displays_the_compatible_release_and_refresh_action(): void
    {
        [$exam] = $this->exam();
        $exam->update(['lan_sync_token' => 'opaque-test-token']);

        $this->get(route('cbt.lan'))->assertOk()
            ->assertSee(CbtLanController::APPLICATION_RELEASE)
            ->assertSee('Sync / refresh');
    }

    public function test_autosave_and_integrity_events_keep_the_lan_attempt_dirty(): void
    {
        [$exam] = $this->exam();
        $session = $this->attempt($exam, 'in_progress', null, now());
        $this->actingAs($this->studentUser);

        $this->postJson(route('cbt.session.autosave', $session), ['answers' => ['1' => 'a']])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertNull($session->fresh()->last_synced_at);

        $session->update(['last_synced_at' => now()]);
        $this->postJson(route('cbt.session.integrity', $session), [
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'fullscreen_exit',
        ])->assertOk()->assertJson(['recorded' => true]);
        $this->assertNull($session->fresh()->last_synced_at);
    }

    public function test_sync_pushes_only_completed_attempts_and_refreshes_cloud_exam_settings(): void
    {
        [$exam] = $this->exam();
        $exam->update(['lan_sync_token' => 'opaque-test-token', 'duration_minutes' => 60]);
        $inProgress = $this->attempt($exam, 'in_progress', null, null, 1);
        $completed = $this->attempt($exam, 'submitted', now(), null, 2);

        Http::fake([
            'https://educoreng.online/api/lan/sync' => Http::response([
                'status' => 'ok', 'accepted' => [$completed->id], 'rejected' => [],
                'exam' => [
                    'id' => $exam->id, 'tenant_id' => $this->ids['tenant'], 'status' => 'published',
                    'scheduled_start' => now()->addHour()->toDateTimeString(),
                    'scheduled_end' => now()->addHours(3)->toDateTimeString(),
                    'duration_minutes' => 90, 'shuffle_questions' => true, 'shuffle_options' => false,
                    'malpractice_enabled' => true, 'focus_loss_policy' => 'submit', 'max_focus_losses' => 1,
                    'require_fullscreen' => true, 'retake_policy' => 'latest_valid_authorized_attempt',
                    'strict_marks_validation' => true, 'updated_at' => now()->toDateTimeString(),
                ],
                'retake_authorizations' => [[
                    'id' => 901, 'tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id,
                    'student_id' => $this->student->id, 'attempt_number' => 2,
                    'reason' => 'Approved LAN retake', 'authorized_at' => now()->toDateTimeString(),
                    'used_at' => null, 'revoked_at' => null, 'revocation_reason' => null,
                    'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                ]],
            ], 200),
        ]);

        $this->postJson(route('cbt.lan.sync', $exam))->assertOk()
            ->assertJson(['status' => 'synced', 'count' => 1]);

        Http::assertSent(function ($request) use ($completed) {
            $sessions = $request['sessions'];
            return $request['client']['application_release'] === CbtLanController::APPLICATION_RELEASE
                && count($sessions) === 1
                && (int) $sessions[0]['id'] === (int) $completed->id;
        });
        $this->assertNotNull($completed->fresh()->last_synced_at);
        $this->assertNull($inProgress->fresh()->last_synced_at);
        $this->assertSame(90, $exam->fresh()->duration_minutes);
        $this->assertTrue($exam->fresh()->require_fullscreen);
        $this->assertDatabaseHas('cbt_retake_authorizations', [
            'id' => 901, 'cbt_exam_id' => $exam->id, 'student_id' => $this->student->id,
            'attempt_number' => 2,
        ]);
    }

    public function test_cloud_api_rejects_incompatible_lan_release(): void
    {
        [$exam] = $this->exam();
        $token = Crypt::encryptString($this->ids['tenant'].'|'.$exam->id.'|'.now()->addHour()->timestamp);

        $this->postJson('/api/lan/sync', [
            'token' => $token,
            'client' => ['package_version' => 2, 'application_release' => 'outdated'],
            'sessions' => [],
        ])->assertStatus(409)->assertJsonPath('server_release', CbtLanController::APPLICATION_RELEASE);
    }

    public function test_cloud_api_accepts_an_empty_compatible_poll_for_schedule_refresh(): void
    {
        [$exam] = $this->exam();
        $token = Crypt::encryptString($this->ids['tenant'].'|'.$exam->id.'|'.now()->addHour()->timestamp);

        $this->postJson('/api/lan/sync', [
            'token' => $token,
            'client' => [
                'package_version' => CbtLanController::PACKAGE_VERSION,
                'application_release' => CbtLanController::APPLICATION_RELEASE,
            ],
            'sessions' => [],
        ])->assertOk()
            ->assertJson(['status' => 'ok', 'accepted' => [], 'rejected' => []])
            ->assertJsonPath('exam.id', $exam->id);
    }

    public function test_cloud_api_imports_completed_scores_and_integrity_events(): void
    {
        [$exam, $section, $question] = $this->exam();
        $token = Crypt::encryptString($this->ids['tenant'].'|'.$exam->id.'|'.now()->addHour()->timestamp);
        $lanSessionId = 98765;
        $eventUuid = (string) Str::uuid();

        $this->postJson('/api/lan/sync', [
            'token' => $token,
            'client' => [
                'package_version' => CbtLanController::PACKAGE_VERSION,
                'application_release' => CbtLanController::APPLICATION_RELEASE,
            ],
            'sessions' => [[
                'id' => $lanSessionId, 'student_id' => $this->student->id, 'attempt_number' => 1,
                'is_authorized_attempt' => true, 'is_active_result' => false,
                'question_order' => [$question->id], 'answers' => [$question->id => 'a'],
                'essay_answers' => [], 'flagged_questions' => [], 'started_at' => now()->subHour()->toIso8601String(),
                'submitted_at' => now()->toIso8601String(), 'score' => 1, 'raw_score' => 1,
                'maximum_score' => 1, 'percentage' => 100, 'status' => 'graded',
                'manual_scores' => [], 'grading_completed_at' => now()->toIso8601String(),
                'submission_reason' => 'student_submit',
                'section_attempts' => [[
                    'cbt_exam_section_id' => $section->id, 'raw_score' => 1, 'maximum_score' => 1,
                    'status' => 'graded', 'scored_at' => now()->toIso8601String(),
                ]],
                'question_scores' => [[
                    'cbt_exam_section_id' => $section->id, 'cbt_question_id' => $question->id,
                    'score' => 1, 'maximum_score' => 1, 'scoring_method' => 'automatic',
                    'status' => 'graded', 'scored_at' => now()->toIso8601String(),
                ]],
                'integrity_events' => [[
                    'event_uuid' => $eventUuid, 'event_type' => 'fullscreen_exit', 'severity' => 'critical',
                    'metadata' => ['source' => 'lan'], 'occurred_at' => now()->toIso8601String(),
                ]],
            ]],
        ])->assertOk()->assertJson(['status' => 'ok', 'accepted' => [$lanSessionId], 'rejected' => []]);

        $cloudSession = CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->where('student_id', $this->student->id)->where('attempt_number', 1)->firstOrFail();
        $this->assertSame('graded', $cloudSession->status);
        $this->assertDatabaseHas('cbt_section_attempts', [
            'cbt_student_session_id' => $cloudSession->id, 'cbt_exam_section_id' => $section->id,
        ]);
        $this->assertDatabaseHas('cbt_question_scores', [
            'cbt_student_session_id' => $cloudSession->id, 'cbt_question_id' => $question->id,
        ]);
        $this->assertDatabaseHas('cbt_integrity_events', [
            'cbt_student_session_id' => $cloudSession->id, 'event_uuid' => $eventUuid,
        ]);
    }

    public function test_import_rejects_an_outdated_package_before_writing_data(): void
    {
        [$exam] = $this->exam();
        $package = UploadedFile::fake()->createWithContent('old-lan-package.json', json_encode([
            'package_version' => 2, 'required_lan_release' => 'outdated',
            'exam_id' => $exam->id, 'tenant_id' => $this->ids['tenant'], 'sync_token' => 'old',
            'tables' => ['cbt_exams' => []],
        ], JSON_THROW_ON_ERROR));

        $this->post(route('cbt.lan.import'), ['package' => $package])
            ->assertSessionHasErrors('error');
    }

    /** @return array{CbtExam,CbtExamSection,CbtQuestion} */
    private function exam(): array
    {
        $bank = CbtQuestionBank::create([
            'tenant_id' => $this->ids['tenant'], 'subject_id' => $this->ids['subject'],
            'class_level_id' => $this->ids['level'], 'name' => 'LAN Biology Bank', 'is_active' => true,
        ]);
        $exam = CbtExam::create([
            'tenant_id' => $this->ids['tenant'], 'question_bank_id' => $bank->id,
            'term_id' => $this->ids['term'], 'class_arm_id' => $this->ids['arm'],
            'assessment_type_id' => $this->ids['assessment'], 'title' => 'LAN Biology Examination',
            'duration_minutes' => 60, 'total_questions' => 1, 'total_marks' => 1,
            'status' => 'published', 'malpractice_enabled' => true, 'focus_loss_policy' => 'submit',
            'max_focus_losses' => 3, 'require_fullscreen' => false,
            'retake_policy' => 'latest_valid_authorized_attempt', 'strict_marks_validation' => true,
        ]);
        $exam->classArms()->attach($this->ids['arm'], ['tenant_id' => $this->ids['tenant']]);
        $section = $exam->sections()->create([
            'tenant_id' => $this->ids['tenant'], 'name' => 'Section A', 'code' => 'A',
            'display_order' => 1, 'section_type' => 'objective', 'scoring_method' => 'automatic',
            'answer_mode' => 'online', 'max_marks' => 1, 'is_required' => true, 'is_active' => true,
        ]);
        $question = CbtQuestion::create([
            'tenant_id' => $this->ids['tenant'], 'question_bank_id' => $bank->id,
            'type' => 'mcq', 'question_text' => 'Which answer is correct?',
            'option_a' => 'Correct', 'option_b' => 'Incorrect', 'correct_answer_letter' => 'a',
            'marks' => 1, 'level' => 0, 'sequence' => 1, 'numbering_style' => 'auto',
            'is_instruction_only' => false, 'requires_answer' => true, 'scoring_method' => 'automatic',
        ]);
        $section->questions()->attach($question->id, [
            'tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id,
            'display_order' => 1, 'marks_override' => 1,
        ]);

        return [$exam, $section, $question];
    }

    private function attempt(
        CbtExam $exam,
        string $status,
        mixed $submittedAt,
        mixed $lastSyncedAt,
        int $attemptNumber = 1
    ): CbtStudentSession {
        return CbtStudentSession::create([
            'tenant_id' => $this->ids['tenant'], 'cbt_exam_id' => $exam->id,
            'student_id' => $this->student->id, 'attempt_number' => $attemptNumber,
            'is_authorized_attempt' => true, 'is_active_result' => false,
            'question_order' => [], 'answers' => [], 'essay_answers' => [], 'flagged_questions' => [],
            'started_at' => now()->subMinutes(20), 'submitted_at' => $submittedAt,
            'last_synced_at' => $lastSyncedAt, 'score' => $submittedAt ? 1 : null,
            'raw_score' => $submittedAt ? 1 : null, 'maximum_score' => 1,
            'percentage' => $submittedAt ? 100 : null, 'status' => $status,
            'grading_completed_at' => $status === 'graded' ? now() : null,
        ]);
    }
}
