<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_question_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_level_id');
            $table->foreign('tenant_id', 'fk_cbtbanks_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subject_id', 'fk_cbtbanks_subject')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_cbtbanks_classlevel')->references('id')->on('class_levels')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id','subject_id','class_level_id'], 'idx_cbtbanks_subject_level');
        });

        Schema::create('cbt_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->foreign('tenant_id', 'fk_cbtqs_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('question_bank_id', 'fk_cbtqs_bank')->references('id')->on('cbt_question_banks')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_image_path')->nullable();
            $table->json('options');
            $table->unsignedTinyInteger('correct_option');
            $table->string('explanation')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->timestamps();
            $table->index(['tenant_id', 'question_bank_id'], 'idx_cbtqs_bank');
        });

        Schema::create('cbt_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->foreign('tenant_id', 'fk_cbtexams_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('question_bank_id', 'fk_cbtexams_bank')->references('id')->on('cbt_question_banks')->restrictOnDelete();
            $table->foreign('term_id', 'fk_cbtexams_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_cbtexams_classarm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->string('title');
            $table->integer('duration_minutes')->default(60);
            $table->integer('total_questions');
            $table->decimal('total_marks', 6, 2)->default(100);
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->enum('status', ['draft','published','active','closed'])->default('draft');
            $table->timestamps();
            $table->index(['tenant_id','class_arm_id','term_id'], 'idx_cbtexams_arm_term');
            $table->index(['tenant_id', 'status'], 'idx_cbtexams_status');
        });

        Schema::create('cbt_student_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('student_id');
            $table->foreign('tenant_id', 'fk_cbtsessions_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtsessions_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_cbtsessions_student')->references('id')->on('students')->cascadeOnDelete();
            $table->json('question_order');
            $table->json('answers')->nullable();
            $table->json('flagged_questions')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('status', ['in_progress','submitted','timed_out'])->default('in_progress');
            $table->timestamps();
            $table->unique(['tenant_id','cbt_exam_id','student_id'], 'uq_cbtsession');
            $table->index(['tenant_id','cbt_exam_id','status'], 'idx_cbtsessions_exam_status');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->foreign('tenant_id', 'fk_attendance_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_attendance_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_attendance_classarm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_attendance_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('marked_by', 'fk_attendance_markedby')->references('id')->on('users')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present','absent','late','excused'])->default('present');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','student_id','attendance_date'], 'uq_daily_attendance');
            $table->index(['tenant_id','class_arm_id','attendance_date'], 'idx_attendance_arm_date');
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->foreign('tenant_id', 'fk_notif_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_notif_student')->references('id')->on('students')->nullOnDelete();
            $table->foreign('guardian_id', 'fk_notif_guardian')->references('id')->on('guardians')->nullOnDelete();
            $table->string('channel');
            $table->string('recipient');
            $table->text('message');
            $table->enum('status', ['queued','sent','failed','delivered'])->default('queued');
            $table->string('gateway_message_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->decimal('unit_cost', 8, 4)->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','channel','status'], 'idx_notif_channel_status');
            $table->index(['tenant_id', 'sent_at'], 'idx_notif_sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('cbt_student_sessions');
        Schema::dropIfExists('cbt_exams');
        Schema::dropIfExists('cbt_questions');
        Schema::dropIfExists('cbt_question_banks');
    }
};
