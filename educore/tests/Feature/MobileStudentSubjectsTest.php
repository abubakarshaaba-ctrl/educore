<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AcademicTrack;
use App\Models\ApiToken;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileStudentSubjectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile student-subject tests require sqlite :memory:.');
        }

        foreach ([
            'student_subject_selections', 'students', 'subjects', 'academic_tracks',
            'academic_sessions', 'api_tokens', 'users', 'tenants',
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
        Schema::create('academic_tracks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('section')->default('general');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
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
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('selection_type')->default('compulsory');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_student_sees_only_own_active_current_session_subjects(): void
    {
        [$tenant, $user, $student] = $this->studentSchool('Current Subject School');
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $oldSession = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2025/2026', 'is_current' => false]);
        $track = AcademicTrack::create(['tenant_id' => $tenant->id, 'name' => 'Science', 'slug' => 'science-'.$tenant->id, 'section' => 'senior', 'is_active' => true]);
        $biology = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO', 'is_active' => true]);
        $chemistry = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Chemistry', 'code' => 'CHE', 'is_active' => true]);
        $inactive = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Old Subject', 'code' => 'OLD', 'is_active' => false]);

        $otherUser = User::create(['tenant_id' => $tenant->id, 'name' => 'Other Student User', 'role' => 'student', 'is_active' => true]);
        $otherStudent = Student::create(['tenant_id' => $tenant->id, 'user_id' => $otherUser->id, 'first_name' => 'Other', 'last_name' => 'Student', 'status' => Student::STATUS_ACTIVE]);

        $this->selection($tenant->id, $student->id, $biology->id, $session->id, 'compulsory', $track->id, true);
        $this->selection($tenant->id, $student->id, $chemistry->id, $session->id, 'elective', $track->id, true);
        $this->selection($tenant->id, $student->id, $inactive->id, $session->id, 'elective', $track->id, true);
        $this->selection($tenant->id, $student->id, $biology->id, $oldSession->id, 'compulsory', $track->id, true);
        $this->selection($tenant->id, $student->id, $chemistry->id, $session->id, 'elective', $track->id, false);
        $this->selection($tenant->id, $otherStudent->id, $chemistry->id, $session->id, 'compulsory', $track->id, true);

        $response = $this->withToken(ApiToken::issue($user, 'student-subjects'))
            ->getJson('/api/v1/operations/subjects')
            ->assertOk()
            ->assertJsonPath('module.key', 'subjects')
            ->assertJsonPath('module.title', 'My Subjects')
            ->assertJsonPath('module.can_manage', false)
            ->assertJsonPath('metrics.0.value', '2')
            ->assertJsonPath('metrics.1.value', '1')
            ->assertJsonPath('metrics.2.value', '1')
            ->assertJsonCount(2, 'sections.0.records');

        $titles = collect($response->json('sections.0.records'))->pluck('title');
        $this->assertTrue($titles->contains('Biology'));
        $this->assertTrue($titles->contains('Chemistry'));
        $this->assertFalse($titles->contains('Old Subject'));
    }

    public function test_foreign_tenant_subject_selection_is_never_exposed(): void
    {
        [$tenant, $user, $student] = $this->studentSchool('Boundary Subject School');
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $own = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Physics', 'code' => 'PHY', 'is_active' => true]);

        [$foreignTenant] = $this->studentSchool('Foreign Subject School');
        $foreignSubject = Subject::create(['tenant_id' => $foreignTenant->id, 'name' => 'Foreign Economics', 'code' => 'FEC', 'is_active' => true]);

        $this->selection($tenant->id, $student->id, $own->id, $session->id, 'compulsory', null, true);
        // Deliberately malformed cross-tenant row: controller must fail closed.
        $this->selection($tenant->id, $student->id, $foreignSubject->id, $session->id, 'elective', null, true);

        $response = $this->withToken(ApiToken::issue($user, 'student-subject-boundary'))
            ->getJson('/api/v1/operations/subjects')
            ->assertOk()
            ->assertJsonCount(1, 'sections.0.records');

        $this->assertSame('Physics', $response->json('sections.0.records.0.title'));
    }

    public function test_inactive_student_or_missing_current_session_gets_empty_current_workspace(): void
    {
        [$tenant, $user, $student] = $this->studentSchool('Inactive Subject School');
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2025/2026', 'is_current' => false]);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
        $this->selection($tenant->id, $student->id, $subject->id, $session->id, 'compulsory', null, true);
        $student->update(['status' => Student::STATUS_SUSPENDED]);

        $this->withToken(ApiToken::issue($user, 'student-subject-empty'))
            ->getJson('/api/v1/operations/subjects')
            ->assertOk()
            ->assertJsonPath('metrics.0.value', '0')
            ->assertJsonCount(0, 'sections.0.records');
    }

    public function test_non_student_cannot_receive_student_subject_workspace(): void
    {
        $tenant = Tenant::create(['name' => 'Staff Boundary School', 'slug' => 'staff-boundary-'.uniqid(), 'status' => 'active']);
        $staff = User::create(['tenant_id' => $tenant->id, 'name' => 'Teacher', 'role' => 'teacher', 'is_active' => true]);

        // The same endpoint is valid for authorized staff only through the normal
        // operations service; an unprivileged teacher must not inherit student access.
        $this->withToken(ApiToken::issue($staff, 'staff-subject-boundary'))
            ->getJson('/api/v1/operations/subjects')
            ->assertForbidden();
    }

    private function studentSchool(string $name): array
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => 'active']);
        $user = User::create(['tenant_id' => $tenant->id, 'name' => $name.' Student', 'role' => 'student', 'is_active' => true]);
        $student = Student::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'admission_number' => 'STU-'.strtoupper(substr(md5($name), 0, 6)),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'status' => Student::STATUS_ACTIVE,
        ]);

        return [$tenant, $user, $student];
    }

    private function selection(
        int $tenantId,
        int $studentId,
        int $subjectId,
        int $sessionId,
        string $type,
        ?int $trackId,
        bool $active
    ): void {
        DB::table('student_subject_selections')->insert([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'academic_track_id' => $trackId,
            'subject_id' => $subjectId,
            'selection_type' => $type,
            'session_id' => $sessionId,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
