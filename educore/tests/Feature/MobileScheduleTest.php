<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ExamPeriod;
use App\Models\ExamSession;
use App\Models\ExamSupervisor;
use App\Models\ExamTimetableEntry;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile schedule tests require sqlite :memory:.');
        }

        foreach ([
            'api_tokens', 'exam_supervisors', 'exam_timetable_entries', 'exam_sessions', 'exam_periods',
            'timetable_periods', 'students', 'subjects', 'class_arms', 'class_levels', 'terms',
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
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('timetable_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('session_id');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('term_id');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('excluded_weekdays')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('exam_timetable_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('exam_date');
            $table->unsignedBigInteger('exam_session_id');
            $table->string('venue')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_supervisors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_timetable_entry_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    public function test_staff_schedule_uses_current_session_and_published_current_term_duties(): void
    {
        $school = $this->school();
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $old = AcademicSession::create(['tenant_id' => $school['tenant']->id, 'name' => '2025/2026', 'is_current' => false]);
        $this->period($school, $teacher, $school['session']->id, 'Monday');
        $this->period($school, $teacher, $old->id, 'Tuesday');
        $publishedEntry = $this->examEntry($school, 'published', 1);
        $draftEntry = $this->examEntry($school, 'draft', 2);
        foreach ([$publishedEntry, $draftEntry] as $entry) {
            ExamSupervisor::create(['tenant_id' => $school['tenant']->id, 'exam_timetable_entry_id' => $entry->id, 'user_id' => $teacher->id]);
        }

        $this->withToken(ApiToken::issue($teacher, 'schedule-test'))->getJson('/api/v1/schedule')
            ->assertOk()->assertJsonPath('contract_version', 1)->assertJsonPath('scope.type', 'staff')
            ->assertJsonCount(1, 'week.0.periods')->assertJsonCount(0, 'week.1.periods')
            ->assertJsonCount(1, 'duties')->assertJsonPath('duties.0.subject', 'Biology');
    }

    public function test_student_receives_only_own_class_published_exam_schedule(): void
    {
        $school = $this->school();
        $studentUser = $this->user($school['tenant'], 'student');
        Student::create([
            'tenant_id' => $school['tenant']->id, 'user_id' => $studentUser->id, 'current_class_arm_id' => $school['class']->id,
            'admission_number' => 'STU001', 'first_name' => 'Amina', 'last_name' => 'Bello', 'status' => Student::STATUS_ACTIVE,
        ]);
        $this->examEntry($school, 'published', 1);
        $this->examEntry($school, 'draft', 2);

        $this->withToken(ApiToken::issue($studentUser, 'student-schedule'))->getJson('/api/v1/schedule')
            ->assertOk()->assertJsonPath('scope.type', 'student')->assertJsonPath('scope.class.id', $school['class']->id)
            ->assertJsonCount(1, 'exams')->assertJsonCount(0, 'duties');
    }

    public function test_teacher_cannot_open_an_unassigned_class_schedule(): void
    {
        $school = $this->school();
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $this->withToken(ApiToken::issue($teacher, 'forbidden-schedule'))
            ->getJson("/api/v1/schedule?class_arm_id={$school['class']->id}")->assertForbidden();
    }

    private function school(): array
    {
        $tenant = Tenant::create(['name' => 'Greenfield Academy', 'slug' => 'greenfield-'.uniqid(), 'status' => 'active']);
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $term = Term::create(['tenant_id' => $tenant->id, 'session_id' => $session->id, 'name' => 'First Term', 'is_current' => true]);
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'SS 2', 'section' => 'senior']);
        $class = ClassArm::create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'A']);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO']);

        return compact('tenant', 'session', 'term', 'level', 'class', 'subject');
    }

    private function user(Tenant $tenant, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id, 'name' => str($role)->headline().' '.uniqid(), 'role' => $role,
            'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function period(array $school, User $teacher, int $sessionId, string $day): TimetablePeriod
    {
        return TimetablePeriod::create([
            'tenant_id' => $school['tenant']->id, 'class_arm_id' => $school['class']->id,
            'subject_id' => $school['subject']->id, 'teacher_id' => $teacher->id, 'session_id' => $sessionId,
            'day_of_week' => $day, 'start_time' => '08:00:00', 'end_time' => '08:40:00', 'venue' => 'Room 2',
        ]);
    }

    private function examEntry(array $school, string $status, int $dayOffset): ExamTimetableEntry
    {
        $date = today()->addDays($dayOffset);
        $period = ExamPeriod::create([
            'tenant_id' => $school['tenant']->id, 'term_id' => $school['term']->id, 'title' => ucfirst($status).' Examination',
            'start_date' => $date, 'end_date' => $date, 'status' => $status,
        ]);
        $session = ExamSession::create([
            'tenant_id' => $school['tenant']->id, 'exam_period_id' => $period->id, 'name' => 'Morning',
            'start_time' => '09:00:00', 'end_time' => '11:00:00', 'sort_order' => 1,
        ]);

        return ExamTimetableEntry::create([
            'tenant_id' => $school['tenant']->id, 'exam_period_id' => $period->id,
            'class_level_id' => $school['level']->id, 'subject_id' => $school['subject']->id,
            'exam_date' => $date, 'exam_session_id' => $session->id, 'venue' => 'Main Hall',
        ]);
    }
}
