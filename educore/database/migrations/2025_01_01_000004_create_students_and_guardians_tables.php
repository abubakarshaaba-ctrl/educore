<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('tenant_id', 'fk_guardians_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id', 'fk_guardians_user')->references('id')->on('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->string('address')->nullable();
            $table->enum('relationship', ['father','mother','guardian','other'])->default('guardian');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id'], 'idx_guardians_tenant');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->foreign('tenant_id', 'fk_students_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id', 'fk_students_user')->references('id')->on('users')->nullOnDelete();
            $table->foreign('current_class_arm_id', 'fk_students_classarm')->references('id')->on('class_arms')->nullOnDelete();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->enum('gender', ['male','female','other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->string('lga_of_origin')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('passport_photo_path')->nullable();
            $table->enum('status', ['applicant','active','suspended','graduated','withdrawn'])->default('applicant');
            $table->date('admission_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'admission_number'], 'uq_students_tenant_admno');
            $table->index(['tenant_id', 'status'], 'idx_students_tenant_status');
            $table->index(['tenant_id', 'current_class_arm_id'], 'idx_students_tenant_arm');
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('student_id');
            $table->foreign('tenant_id', 'fk_guardstudt_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('guardian_id', 'fk_guardstudt_guardian')->references('id')->on('guardians')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_guardstudt_student')->references('id')->on('students')->cascadeOnDelete();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id'], 'uq_guardstudt_pair');
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->foreign('tenant_id', 'fk_enrollments_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_enrollments_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_enrollments_classarm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_enrollments_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_enrollments_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id','student_id','session_id','term_id'], 'uq_enrollment_unique');
            $table->index(['tenant_id','class_arm_id','session_id'], 'idx_enrollment_arm_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('students');
        Schema::dropIfExists('guardians');
    }
};
