<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parallel_curriculum_timetable_periods')) {
            Schema::create('parallel_curriculum_timetable_periods', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('parallel_curriculum_class_arm_id');
                $table->unsignedBigInteger('parallel_curriculum_subject_id');
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->unsignedBigInteger('session_id');
                $table->enum('day_of_week', ['monday','tuesday','wednesday','thursday','friday']);
                $table->time('start_time');
                $table->time('end_time');
                $table->string('venue', 100)->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_tt_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_tt_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_tt_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_arm_id', 'fk_pc_tt_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_subject_id', 'fk_pc_tt_subject')
                    ->references('id')->on('parallel_curriculum_subjects')->cascadeOnDelete();
                $table->foreign('teacher_id', 'fk_pc_tt_teacher')
                    ->references('id')->on('users')->nullOnDelete();
                $table->foreign('session_id', 'fk_pc_tt_session')
                    ->references('id')->on('academic_sessions')->cascadeOnDelete();

                $table->index(
                    ['tenant_id', 'parallel_curriculum_class_arm_id', 'session_id', 'day_of_week'],
                    'idx_pc_tt_arm_session_day'
                );
                $table->index(
                    ['tenant_id', 'teacher_id', 'session_id', 'day_of_week'],
                    'idx_pc_tt_teacher_session_day'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_attendance_records')) {
            Schema::create('parallel_curriculum_attendance_records', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('parallel_curriculum_class_arm_id');
                $table->unsignedBigInteger('parallel_curriculum_enrolment_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('marked_by')->nullable();
                $table->date('attendance_date');
                $table->enum('status', ['present','absent','late','excused'])->default('present');
                $table->string('remark', 200)->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_att_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_att_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_att_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_arm_id', 'fk_pc_att_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_enrolment_id', 'fk_pc_att_enrol')
                    ->references('id')->on('parallel_curriculum_enrolments')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_pc_att_student')
                    ->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('term_id', 'fk_pc_att_term')
                    ->references('id')->on('terms')->cascadeOnDelete();
                $table->foreign('marked_by', 'fk_pc_att_marker')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(
                    ['tenant_id', 'parallel_curriculum_enrolment_id', 'attendance_date'],
                    'uq_pc_att_enrol_date'
                );
                $table->index(
                    ['tenant_id', 'parallel_curriculum_class_arm_id', 'term_id', 'attendance_date'],
                    'idx_pc_att_arm_term_date'
                );
                $table->index(
                    ['tenant_id', 'student_id', 'term_id'],
                    'idx_pc_att_student_term'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_attendance_records');
        Schema::dropIfExists('parallel_curriculum_timetable_periods');
    }
};
