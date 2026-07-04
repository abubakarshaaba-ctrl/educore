<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('term_id');
            $table->foreign('tenant_id', 'fk_asstypes_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_asstypes_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('weight_percentage');
            $table->boolean('is_exam')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'term_id'], 'idx_asstypes_tenant_term');
        });

        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->foreign('tenant_id', 'fk_grading_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_grading_classlevel')->references('id')->on('class_levels')->cascadeOnDelete();
            $table->string('grade_letter', 5);
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('remark');
            $table->boolean('is_pass_grade')->default(true);
            $table->integer('grade_point')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'class_level_id'], 'idx_grading_tenant_level');
        });

        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->foreign('tenant_id', 'fk_promrules_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_promrules_classlevel')->references('id')->on('class_levels')->cascadeOnDelete();
            $table->unsignedTinyInteger('min_required_average')->default(50);
            $table->unsignedTinyInteger('max_failed_subjects_allowed')->default(2);
            $table->json('compulsory_subject_ids')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'class_level_id'], 'uq_promrules_tenant_level');
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('assessment_type_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->foreign('tenant_id', 'fk_scores_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_scores_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('subject_id', 'fk_scores_subject')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('assessment_type_id', 'fk_scores_asstype')->references('id')->on('assessment_types')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_scores_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_scores_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->foreign('entered_by', 'fk_scores_enteredby')->references('id')->on('users')->nullOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','student_id','subject_id','assessment_type_id','term_id'], 'uq_score_entry');
            $table->index(['tenant_id','term_id','subject_id'], 'idx_scores_term_subject');
            $table->index(['tenant_id','student_id','term_id'], 'idx_scores_student_term');
        });

        Schema::create('termly_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->foreign('tenant_id', 'fk_termsummary_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_termsummary_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_termsummary_classarm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_termsummary_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_termsummary_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('final_average', 5, 2)->default(0);
            $table->integer('position_in_class')->nullable();
            $table->integer('total_students_in_class')->nullable();
            $table->integer('subjects_offered')->default(0);
            $table->integer('subjects_failed')->default(0);
            $table->json('subject_breakdown')->nullable();
            $table->enum('promotion_status', ['pending','promoted','repeat','graduated'])->default('pending');
            $table->text('form_tutor_remark')->nullable();
            $table->text('principal_remark')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','student_id','term_id','session_id'], 'uq_termsummary');
            $table->index(['tenant_id','class_arm_id','term_id'], 'idx_termsummary_arm_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termly_summaries');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('grading_systems');
        Schema::dropIfExists('assessment_types');
    }
};
