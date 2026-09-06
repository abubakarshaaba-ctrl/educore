<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileClassWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile class workspace tests require sqlite :memory:.');
        }

        foreach ([
            'mobile_idempotency_keys', 'api_tokens', 'attendance_records', 'class_arm_subjects', 'students', 'subjects',
            'class_arms', 'academic_tracks', 'class_levels', 'terms', 'academic_sessions', 'users', 'tenants',
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
        $this->createIdempotencyTable();
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
        Schema::create('academic_tracks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('section')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
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
            $table->unsignedBigInteger('term_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->date('attendance_date');
            $table->string('status');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id', 'attendance_date']);
        });
    }

    public function test_form_tutor_and_subject_assignment_are_merged_into_one_class(): void
    {
        [$tenant, $session] = $this->school('greenfield');
        $teacher = $this->user($tenant, 'form_subject_teacher');
        $classArm = $this->classArm($tenant, $teacher);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO']);
        ClassArmSubject::create([
            'tenant_id' => $tenant->id,
            'class_arm_id' => $classArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
        ]);
        $this->student($tenant, $classArm, 'STU001');

        $this->withToken(ApiToken::issue($teacher, 'class-test'))
            ->getJson('/api/v1/classes')
            ->assertOk()
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $classArm->id)
            ->assertJsonPath('classes.0.roles.0', 'form_tutor')
            ->assertJsonPath('classes.0.roles.1', 'subject_teacher')
            ->assertJsonPath('classes.0.students_count', 1)
            ->assertJsonPath('classes.0.capabilities.mark_attendance', true);
    }

    public function test_subject_teacher_can_read_students_but_cannot_mark_attendance(): void
    {
        [$tenant, $session] = $this->school('subject-school');
        $formTutor = $this->user($tenant, 'form_teacher');
        $subjectTeacher = $this->user($tenant, 'subject_teacher');
        $classArm = $this->classArm($tenant, $formTutor);
        $student = $this->student($tenant, $classArm, 'STU002');
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        ClassArmSubject::create([
            'tenant_id' => $tenant->id,
            'class_arm_id' => $classArm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
            'session_id' => $session->id,
        ]);
        $token = ApiToken::issue($subjectTeacher, 'subject-test');

        $this->withToken($token)
            ->getJson("/api/v1/classes/{$classArm->id}/students")
            ->assertOk()
            ->assertJsonPath('students.0.id', $student->id)
            ->assertJsonPath('class.capabilities.mark_attendance', false);

        $this->withToken($token)
            ->getJson("/api/v1/classes/{$classArm->id}/attendance")
            ->assertForbidden();
    }

    public function test_administrator_can_save_attendance_and_stale_version_is_rejected(): void
    {
        [$tenant] = $this->school('admin-school');
        $administrator = $this->user($tenant, 'admin');
        $classArm = $this->classArm($tenant, null);
        $student = $this->student($tenant, $classArm, 'STU003');
        $token = ApiToken::issue($administrator, 'admin-test');

        $this->withToken($token)
            ->getJson("/api/v1/classes/{$classArm->id}/attendance?date=".today()->toDateString())
            ->assertOk()
            ->assertJsonPath('version', 'empty');

        $payload = [
            'date' => today()->toDateString(),
            'version' => 'empty',
            'request_id' => '0199322b-7cc8-73de-a9f1-3e34cb662dde',
            'records' => [[
                'student_id' => $student->id,
                'status' => 'present',
                'remark' => null,
            ]],
        ];

        $this->withToken($token)
            ->postJson("/api/v1/classes/{$classArm->id}/attendance", $payload)
            ->assertOk()
            ->assertJsonPath('saved', 1)
            ->assertJsonPath('summary.present', 1)
            ->assertJsonPath('request_id', $payload['request_id']);

        $this->withToken($token)
            ->postJson("/api/v1/classes/{$classArm->id}/attendance", $payload)
            ->assertOk()
            ->assertJsonPath('request_id', $payload['request_id'])
            ->assertJsonPath('summary.present', 1);

        $payload['records'][0]['status'] = 'absent';
        $this->withToken($token)->postJson("/api/v1/classes/{$classArm->id}/attendance", $payload)
            ->assertUnprocessable();

        $payload['request_id'] = '1199322b-7cc8-73de-a9f1-3e34cb662dde';
        $this->withToken($token)->postJson("/api/v1/classes/{$classArm->id}/attendance", $payload)
            ->assertConflict()->assertJsonPath('message', 'Attendance changed on the server. Reload the sheet before saving your draft.');
    }

    public function test_class_from_another_tenant_is_not_visible(): void
    {
        [$tenantA] = $this->school('school-a');
        [$tenantB] = $this->school('school-b');
        $adminA = $this->user($tenantA, 'admin');
        $classB = $this->classArm($tenantB, null);

        $this->withToken(ApiToken::issue($adminA, 'tenant-test'))
            ->getJson("/api/v1/classes/{$classB->id}")
            ->assertNotFound();
    }

    private function school(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => 'active']);
        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);
        Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'is_current' => true,
        ]);

        return [$tenant, $session];
    }

    private function user(Tenant $tenant, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => str($role)->headline().' '.uniqid(),
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function classArm(Tenant $tenant, ?User $formTutor): ClassArm
    {
        $level = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'SS 2',
            'section' => 'senior',
        ]);

        return ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'form_tutor_id' => $formTutor?->id,
            'name' => 'A',
        ]);
    }

    private function student(Tenant $tenant, ClassArm $classArm, string $admissionNumber): Student
    {
        return Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $classArm->id,
            'admission_number' => $admissionNumber,
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'gender' => 'female',
            'status' => Student::STATUS_ACTIVE,
        ]);
    }

    private function createIdempotencyTable(): void
    {
        Schema::create('mobile_idempotency_keys', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('user_id');
            $table->string('scope', 100); $table->uuid('request_id'); $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing'); $table->longText('response_json')->nullable();
            $table->timestamps(); $table->unique(['user_id', 'scope', 'request_id']);
        });
    }
}
