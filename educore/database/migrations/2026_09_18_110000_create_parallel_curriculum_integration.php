<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parallel_curricula')) {
            Schema::create('parallel_curricula', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name', 120);
                $table->string('code', 40)->nullable();
                $table->unsignedBigInteger('default_assessment_template_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_curr_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('default_assessment_template_id', 'fk_pc_curr_template')->references('id')->on('assessment_templates')->nullOnDelete();
                $table->unique(['tenant_id', 'name'], 'uq_pc_curr_name');
                $table->index(['tenant_id', 'is_active'], 'idx_pc_curr_active');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_classes')) {
            Schema::create('parallel_curriculum_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('assessment_template_id')->nullable();
                $table->string('name', 120);
                $table->string('code', 40)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_class_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_class_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('assessment_template_id', 'fk_pc_class_template')->references('id')->on('assessment_templates')->nullOnDelete();
                $table->unique(['parallel_curriculum_id', 'name'], 'uq_pc_class_name');
                $table->index(['tenant_id', 'parallel_curriculum_id', 'is_active'], 'idx_pc_class_active');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_class_subjects')) {
            Schema::create('parallel_curriculum_class_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_cs_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_cs_class')->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('subject_id', 'fk_pc_cs_subject')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('teacher_id', 'fk_pc_cs_teacher')->references('id')->on('users')->nullOnDelete();
                $table->unique(['parallel_curriculum_class_id', 'subject_id'], 'uq_pc_class_subject');
                $table->index(['tenant_id', 'teacher_id', 'is_active'], 'idx_pc_cs_teacher');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_enrolments')) {
            Schema::create('parallel_curriculum_enrolments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('session_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_enrol_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_enrol_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_enrol_class')->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_pc_enrol_student')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('session_id', 'fk_pc_enrol_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
                $table->unique(['parallel_curriculum_id', 'student_id', 'session_id'], 'uq_pc_enrol_student_session');
                $table->index(['tenant_id', 'parallel_curriculum_class_id', 'session_id', 'is_active'], 'idx_pc_enrol_class_session');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_scores')) {
            Schema::create('parallel_curriculum_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('assessment_template_component_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('entered_by')->nullable();
                $table->decimal('score', 6, 2)->nullable();
                $table->timestamp('entered_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_score_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_score_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_score_class')->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_pc_score_student')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('subject_id', 'fk_pc_score_subject')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('assessment_template_component_id', 'fk_pc_score_component')->references('id')->on('assessment_template_components')->cascadeOnDelete();
                $table->foreign('term_id', 'fk_pc_score_term')->references('id')->on('terms')->cascadeOnDelete();
                $table->foreign('session_id', 'fk_pc_score_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
                $table->foreign('entered_by', 'fk_pc_score_user')->references('id')->on('users')->nullOnDelete();

                $table->unique(
                    ['parallel_curriculum_id', 'student_id', 'subject_id', 'assessment_template_component_id', 'term_id'],
                    'uq_pc_score_cell'
                );
                $table->index(['tenant_id', 'parallel_curriculum_class_id', 'term_id'], 'idx_pc_score_class_term');
                $table->index(['tenant_id', 'student_id', 'term_id'], 'idx_pc_score_student_term');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_integrations')) {
            Schema::create('parallel_curriculum_integrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('destination_class_level_id');
                $table->unsignedBigInteger('destination_subject_id');
                $table->string('calculation_method', 30)->default('arithmetic_mean');
                $table->boolean('require_all_subjects')->default(true);
                $table->unsignedSmallInteger('minimum_completed_subjects')->default(1);
                $table->boolean('auto_sync')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_int_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_int_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('destination_class_level_id', 'fk_pc_int_level')->references('id')->on('class_levels')->cascadeOnDelete();
                $table->foreign('destination_subject_id', 'fk_pc_int_subject')->references('id')->on('subjects')->cascadeOnDelete();
                $table->unique(['parallel_curriculum_id', 'destination_class_level_id'], 'uq_pc_int_level');
                $table->index(['tenant_id', 'is_active'], 'idx_pc_int_active');
            });
        }

        if (! Schema::hasTable('parallel_curriculum_composites')) {
            Schema::create('parallel_curriculum_composites', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_integration_id')->nullable();
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('conventional_class_arm_id')->nullable();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('destination_subject_id')->nullable();
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('session_id');
                $table->decimal('average_score', 6, 2)->nullable();
                $table->unsignedSmallInteger('subject_count')->default(0);
                $table->unsignedSmallInteger('completed_subject_count')->default(0);
                $table->json('subject_breakdown')->nullable();
                $table->string('sync_status', 30)->default('pending');
                $table->string('sync_message', 255)->nullable();
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_comp_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_comp_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_integration_id', 'fk_pc_comp_int')->references('id')->on('parallel_curriculum_integrations')->nullOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_comp_class')->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('conventional_class_arm_id', 'fk_pc_comp_arm')->references('id')->on('class_arms')->nullOnDelete();
                $table->foreign('student_id', 'fk_pc_comp_student')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('destination_subject_id', 'fk_pc_comp_subject')->references('id')->on('subjects')->nullOnDelete();
                $table->foreign('term_id', 'fk_pc_comp_term')->references('id')->on('terms')->cascadeOnDelete();
                $table->foreign('session_id', 'fk_pc_comp_session')->references('id')->on('academic_sessions')->cascadeOnDelete();

                $table->unique(['parallel_curriculum_id', 'student_id', 'term_id'], 'uq_pc_comp_student_term');
                $table->index(['tenant_id', 'sync_status', 'term_id'], 'idx_pc_comp_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_composites');
        Schema::dropIfExists('parallel_curriculum_integrations');
        Schema::dropIfExists('parallel_curriculum_scores');
        Schema::dropIfExists('parallel_curriculum_enrolments');
        Schema::dropIfExists('parallel_curriculum_class_subjects');
        Schema::dropIfExists('parallel_curriculum_classes');
        Schema::dropIfExists('parallel_curricula');
    }
};
