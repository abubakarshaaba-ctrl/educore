<?php

namespace Tests\Feature\Concerns;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait BuildsAcademicCycleTestSchema
{
    protected function rebuildAcademicCycleSchema(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Academic cycle tests require the isolated sqlite :memory: test database.');
        }

        foreach ([
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'audit_logs',
            'cbt_student_sessions',
            'cbt_exams',
            'payment_transactions',
            'invoices',
            'student_class_transfers',
            'attendance_records',
            'scores',
            'termly_summaries',
            'student_subject_selections',
            'class_level_subjects',
            'class_arm_subjects',
            'promotion_rules',
            'grading_systems',
            'student_enrollments',
            'students',
            'subjects',
            'class_arms',
            'class_levels',
            'terms',
            'academic_sessions',
            'tenant_subscriptions',
            'platform_settings',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTable();
        $this->createUserTable();
        $this->createPermissionTables();
        $this->createAuditLogTable();
        $this->createAcademicTables();
        $this->createStudentTables();
        $this->createAssessmentTables();
        $this->createOperationalTables();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tenantFixture(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Bluerayy Academy',
            'slug' => 'bluerayy-academy-' . uniqid(),
            'email' => 'info@school.test',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ], $overrides));
    }

    protected function actorFixture(Tenant $tenant, array $permissions = []): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Academic Admin',
            'email' => 'academic' . uniqid() . '@school.test',
            'password' => Hash::make('password'),
            'role' => 'academic_administrator',
            'is_active' => true,
            'is_super_admin' => false,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    protected function sessionFixture(Tenant $tenant, string $name = '2025/2026', bool $current = false): AcademicSession
    {
        return AcademicSession::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'is_current' => $current,
        ]);
    }

    protected function termFixture(Tenant $tenant, AcademicSession $session, string $name = 'First Term', bool $current = false): Term
    {
        return Term::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-01',
            'is_current' => $current,
        ]);
    }

    protected function classArmFixture(Tenant $tenant, string $levelName, int $order, string $armName = 'A'): ClassArm
    {
        $level = ClassLevel::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => $levelName,
            'section' => 'primary',
            'order_index' => $order,
        ]);

        GradingSystem::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'grade_letter' => 'A',
            'min_score' => 70,
            'max_score' => 100,
            'remark' => 'Excellent',
            'is_pass_grade' => true,
            'grade_point' => 5,
        ]);

        return ClassArm::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $level->id,
            'name' => $armName,
        ]);
    }

    protected function studentFixture(Tenant $tenant, ClassArm $arm, array $overrides = []): Student
    {
        return Student::withoutTenantScope()->create(array_merge([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $arm->id,
            'admission_number' => 'ADM' . random_int(1000, 9999),
            'first_name' => 'Ada',
            'middle_name' => null,
            'last_name' => 'Student',
            'gender' => 'female',
            'status' => Student::STATUS_ACTIVE,
            'admission_date' => '2025-09-01',
        ], $overrides));
    }

    protected function enrollmentFixture(Tenant $tenant, Student $student, ClassArm $arm, AcademicSession $session, Term $term, bool $current = true): StudentEnrollment
    {
        return StudentEnrollment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'class_arm_id' => $arm->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'start_date' => '2025-09-01',
            'is_current' => $current,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);
    }

    protected function summaryFixture(Tenant $tenant, Student $student, ClassArm $arm, Term $term, string $status): TermlySummary
    {
        return TermlySummary::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'class_arm_id' => $arm->id,
            'term_id' => $term->id,
            'session_id' => $term->session_id,
            'total_score' => 300,
            'final_average' => 75,
            'subjects_offered' => 5,
            'subjects_failed' => 0,
            'promotion_status' => $status,
            'computed_at' => now(),
        ]);
    }

    private function createTenantTable(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('status')->default('active');
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    private function createUserTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createPermissionTables(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    private function createAuditLogTable(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
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

    private function createAcademicTables(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->default('primary');
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->string('grade_letter', 5);
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('remark');
            $table->boolean('is_pass_grade')->default(true);
            $table->integer('grade_point')->default(0);
            $table->timestamps();
        });

        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedTinyInteger('min_required_average')->default(50);
            $table->unsignedTinyInteger('max_failed_subjects_allowed')->default(2);
            $table->json('compulsory_subject_ids')->nullable();
            $table->timestamps();
        });
    }

    private function createStudentTables(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->date('admission_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('status')->default(StudentEnrollment::STATUS_ACTIVE);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->text('ended_reason')->nullable();
            $table->timestamps();
        });
    }

    private function createAssessmentTables(): void
    {
        Schema::create('termly_summaries', function (Blueprint $table) {
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

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('assessment_type_id')->nullable();
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('student_subject_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('selection_type')->default('compulsory');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('class_level_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_status')->default('compulsory');
            $table->string('elective_group')->nullable();
            $table->unsignedTinyInteger('min_required')->nullable();
            $table->unsignedTinyInteger('max_allowed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('class_arm_subjects', function (Blueprint $table) {
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
    }

    private function createOperationalTables(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->date('attendance_date');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('cbt_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->string('title');
            $table->integer('duration_minutes')->default(30);
            $table->integer('total_questions')->default(1);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::create('cbt_student_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('student_id');
            $table->json('question_order')->nullable();
            $table->json('answers')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();
        });

        Schema::create('student_class_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_session_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('from_class_arm_id');
            $table->unsignedBigInteger('to_class_arm_id');
            $table->date('effective_date');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('supporting_document')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('successful');
            $table->string('transaction_reference')->nullable();
            $table->timestamps();
        });
    }
}
