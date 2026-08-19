<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_exam_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->string('name', 120);
            $table->string('code', 20);
            $table->string('title', 180)->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->string('section_type', 30)->default('mixed');
            $table->string('scoring_method', 20)->default('mixed');
            $table->string('answer_mode', 20)->default('online');
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtsections_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtsections_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('created_by', 'fk_cbtsections_creator')->references('id')->on('users')->nullOnDelete();
            $table->unique(['cbt_exam_id', 'code'], 'uq_cbtsections_exam_code');
            $table->index(['tenant_id', 'cbt_exam_id', 'display_order'], 'idx_cbtsections_exam_order');
        });

        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_question_id')->nullable()->after('question_bank_id');
            $table->unsignedSmallInteger('level')->default(0)->after('parent_question_id');
            $table->unsignedInteger('sequence')->default(1)->after('level');
            $table->string('numbering_style', 20)->default('auto')->after('sequence');
            $table->string('reference_code', 80)->nullable()->after('numbering_style');
            $table->boolean('is_instruction_only')->default(false)->after('reference_code');
            $table->boolean('requires_answer')->default(true)->after('is_instruction_only');
            $table->string('scoring_method', 20)->nullable()->after('requires_answer');
            $table->foreign('parent_question_id', 'fk_cbtqs_parent')->references('id')->on('cbt_questions')->cascadeOnDelete();
            $table->index(['question_bank_id', 'parent_question_id', 'sequence'], 'idx_cbtqs_hierarchy');
        });

        Schema::create('cbt_exam_section_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('cbt_exam_section_id');
            $table->unsignedBigInteger('cbt_question_id');
            $table->unsignedInteger('display_order')->default(1);
            $table->decimal('marks_override', 8, 2)->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtsectionqs_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtsectionqs_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('cbt_exam_section_id', 'fk_cbtsectionqs_section')->references('id')->on('cbt_exam_sections')->cascadeOnDelete();
            $table->foreign('cbt_question_id', 'fk_cbtsectionqs_question')->references('id')->on('cbt_questions')->cascadeOnDelete();
            $table->unique(['cbt_exam_section_id', 'cbt_question_id'], 'uq_cbtsectionqs_question');
            $table->index(['cbt_exam_id', 'display_order'], 'idx_cbtsectionqs_exam_order');
        });

        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('class_arm_id');
            $table->boolean('malpractice_enabled')->default(true)->after('shuffle_options');
            $table->string('focus_loss_policy', 20)->default('submit')->after('malpractice_enabled');
            $table->unsignedSmallInteger('max_focus_losses')->default(0)->after('focus_loss_policy');
            $table->boolean('require_fullscreen')->default(false)->after('max_focus_losses');
            $table->string('retake_policy', 60)->default('latest_valid_authorized_attempt')->after('require_fullscreen');
            $table->boolean('strict_marks_validation')->default(true)->after('retake_policy');
            $table->foreign('created_by', 'fk_cbtexams_creator')->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cbt_student_sessions MODIFY status VARCHAR(40) NOT NULL DEFAULT 'in_progress'");
        }

        Schema::table('cbt_student_sessions', function (Blueprint $table) {
            $table->dropUnique('uq_cbtsession');
            $table->unsignedInteger('attempt_number')->default(1)->after('student_id');
            $table->boolean('is_authorized_attempt')->default(true)->after('attempt_number');
            $table->boolean('is_active_result')->default(false)->after('is_authorized_attempt');
            $table->unsignedBigInteger('retake_authorization_id')->nullable()->after('is_active_result');
            $table->timestamp('integrity_acknowledged_at')->nullable()->after('started_at');
            $table->unsignedSmallInteger('focus_loss_count')->default(0)->after('integrity_acknowledged_at');
            $table->string('submission_reason', 80)->nullable()->after('submitted_at');
            $table->decimal('raw_score', 8, 2)->nullable()->after('score');
            $table->decimal('maximum_score', 8, 2)->nullable()->after('raw_score');
            $table->timestamp('grading_completed_at')->nullable()->after('last_synced_at');
            $table->unique(['tenant_id', 'cbt_exam_id', 'student_id', 'attempt_number'], 'uq_cbtsession_attempt');
            $table->index(['tenant_id', 'cbt_exam_id', 'student_id', 'is_active_result'], 'idx_cbtsession_active_result');
        });

        Schema::create('cbt_retake_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('authorized_by');
            $table->unsignedInteger('attempt_number');
            $table->text('reason');
            $table->timestamp('authorized_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtretauth_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtretauth_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_cbtretauth_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('authorized_by', 'fk_cbtretauth_authorizer')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('revoked_by', 'fk_cbtretauth_revoker')->references('id')->on('users')->nullOnDelete();
            $table->unique(['cbt_exam_id', 'student_id', 'attempt_number'], 'uq_cbtretauth_attempt');
            $table->index(['tenant_id', 'cbt_exam_id', 'student_id', 'revoked_at'], 'idx_cbtretauth_lookup');
        });

        Schema::table('cbt_student_sessions', function (Blueprint $table) {
            $table->foreign('retake_authorization_id', 'fk_cbtsession_retauth')->references('id')->on('cbt_retake_authorizations')->nullOnDelete();
        });

        Schema::create('cbt_integrity_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('cbt_student_session_id');
            $table->unsignedBigInteger('student_id');
            $table->uuid('event_uuid');
            $table->string('event_type', 80);
            $table->string('severity', 20)->default('warning');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtintevents_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtintevents_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('cbt_student_session_id', 'fk_cbtintevents_session')->references('id')->on('cbt_student_sessions')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_cbtintevents_student')->references('id')->on('students')->cascadeOnDelete();
            $table->unique('event_uuid', 'uq_cbtintevents_uuid');
            $table->index(['tenant_id', 'cbt_exam_id', 'event_type'], 'idx_cbtintevents_exam_type');
        });

        Schema::create('cbt_section_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_student_session_id');
            $table->unsignedBigInteger('cbt_exam_section_id');
            $table->decimal('raw_score', 8, 2)->nullable();
            $table->decimal('maximum_score', 8, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtsectattempt_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_student_session_id', 'fk_cbtsectattempt_session')->references('id')->on('cbt_student_sessions')->cascadeOnDelete();
            $table->foreign('cbt_exam_section_id', 'fk_cbtsectattempt_section')->references('id')->on('cbt_exam_sections')->cascadeOnDelete();
            $table->unique(['cbt_student_session_id', 'cbt_exam_section_id'], 'uq_cbtsectattempt');
        });

        Schema::create('cbt_question_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_student_session_id');
            $table->unsignedBigInteger('cbt_exam_section_id')->nullable();
            $table->unsignedBigInteger('cbt_question_id');
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('maximum_score', 8, 2)->default(0);
            $table->string('scoring_method', 20);
            $table->string('status', 30)->default('pending');
            $table->unsignedBigInteger('scored_by')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtqscores_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_student_session_id', 'fk_cbtqscores_session')->references('id')->on('cbt_student_sessions')->cascadeOnDelete();
            $table->foreign('cbt_exam_section_id', 'fk_cbtqscores_section')->references('id')->on('cbt_exam_sections')->nullOnDelete();
            $table->foreign('cbt_question_id', 'fk_cbtqscores_question')->references('id')->on('cbt_questions')->cascadeOnDelete();
            $table->foreign('scored_by', 'fk_cbtqscores_marker')->references('id')->on('users')->nullOnDelete();
            $table->unique(['cbt_student_session_id', 'cbt_question_id'], 'uq_cbtqscores_question');
        });

        Schema::create('cbt_import_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->unsignedBigInteger('cbt_exam_id')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('original_name');
            $table->string('status', 30)->default('preview');
            $table->json('rows')->nullable();
            $table->json('validation_errors')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtimports_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('question_bank_id', 'fk_cbtimports_bank')->references('id')->on('cbt_question_banks')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtimports_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('uploaded_by', 'fk_cbtimports_user')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->string('score_source', 30)->default('manual')->after('cbt_exam_id');
            $table->string('source_reference_type', 120)->nullable()->after('score_source');
            $table->unsignedBigInteger('source_reference_id')->nullable()->after('source_reference_type');
            $table->boolean('is_source_locked')->default(false)->after('source_reference_id');
            $table->timestamp('source_synced_at')->nullable()->after('is_source_locked');
            $table->index(['tenant_id', 'score_source', 'source_reference_id'], 'idx_scores_source');
        });

        $this->backfillLegacySections();
        $this->backfillLegacyAttempts();
    }

    private function backfillLegacySections(): void
    {
        DB::table('cbt_exams')->orderBy('id')->each(function ($exam) {
            if (DB::table('cbt_exam_sections')->where('cbt_exam_id', $exam->id)->exists()) {
                return;
            }

            $sections = [];
            $objectiveCount = (int) ($exam->section_objective_count ?? 0);
            $theoryCount = (int) ($exam->section_theory_count ?? 0);

            if ($objectiveCount > 0) {
                $sections[] = ['code' => 'A', 'name' => 'Section A', 'title' => 'Objective Questions', 'type' => 'objective', 'method' => 'automatic', 'count' => $objectiveCount, 'mark' => (float) ($exam->section_objective_marks ?? 1)];
            }
            if ($theoryCount > 0) {
                $sections[] = ['code' => 'B', 'name' => 'Section B', 'title' => 'Theory Questions', 'type' => 'theory', 'method' => 'manual', 'count' => $theoryCount, 'mark' => (float) ($exam->section_theory_marks ?? 5)];
            }
            if ($sections === []) {
                $count = max(1, (int) ($exam->total_questions ?? 1));
                $sections[] = ['code' => 'A', 'name' => 'Section A', 'title' => 'Questions', 'type' => 'mixed', 'method' => 'mixed', 'count' => $count, 'mark' => (float) ($exam->total_marks ?? $count) / $count];
            }

            foreach ($sections as $order => $definition) {
                $sectionId = DB::table('cbt_exam_sections')->insertGetId([
                    'tenant_id' => $exam->tenant_id,
                    'cbt_exam_id' => $exam->id,
                    'name' => $definition['name'],
                    'code' => $definition['code'],
                    'title' => $definition['title'],
                    'display_order' => $order + 1,
                    'section_type' => $definition['type'],
                    'scoring_method' => $definition['method'],
                    'answer_mode' => 'online',
                    'max_marks' => round($definition['count'] * $definition['mark'], 2),
                    'is_required' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $query = DB::table('cbt_questions')->where('question_bank_id', $exam->question_bank_id);
                if ($definition['type'] === 'objective') {
                    $query->whereIn('type', ['mcq', 'true_false', 'fill_blank']);
                } elseif ($definition['type'] === 'theory') {
                    $query->whereIn('type', ['essay', 'short_answer']);
                }
                $questionIds = $query->orderBy('id')->limit($definition['count'])->pluck('id');
                foreach ($questionIds as $questionOrder => $questionId) {
                    DB::table('cbt_exam_section_questions')->insert([
                        'tenant_id' => $exam->tenant_id,
                        'cbt_exam_id' => $exam->id,
                        'cbt_exam_section_id' => $sectionId,
                        'cbt_question_id' => $questionId,
                        'display_order' => $questionOrder + 1,
                        'marks_override' => $definition['mark'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    private function backfillLegacyAttempts(): void
    {
        DB::table('cbt_student_sessions')->whereIn('status', ['graded', 'completed'])->orderBy('id')->each(function ($session) {
            $maximum = (float) (DB::table('cbt_exams')->where('id', $session->cbt_exam_id)->value('total_marks') ?: 0);
            DB::table('cbt_student_sessions')->where('id', $session->id)->update([
                'attempt_number' => 1,
                'is_authorized_attempt' => true,
                'is_active_result' => true,
                'raw_score' => $session->score,
                'maximum_score' => $maximum,
                'grading_completed_at' => $session->submitted_at ?: $session->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropIndex('idx_scores_source');
            $table->dropColumn(['score_source', 'source_reference_type', 'source_reference_id', 'is_source_locked', 'source_synced_at']);
        });
        Schema::dropIfExists('cbt_import_batches');
        Schema::dropIfExists('cbt_question_scores');
        Schema::dropIfExists('cbt_section_attempts');
        Schema::dropIfExists('cbt_integrity_events');
        Schema::table('cbt_student_sessions', fn (Blueprint $table) => $table->dropForeign('fk_cbtsession_retauth'));
        Schema::dropIfExists('cbt_retake_authorizations');
        Schema::table('cbt_student_sessions', function (Blueprint $table) {
            $table->dropUnique('uq_cbtsession_attempt');
            $table->dropIndex('idx_cbtsession_active_result');
            $table->dropColumn(['attempt_number', 'is_authorized_attempt', 'is_active_result', 'retake_authorization_id', 'integrity_acknowledged_at', 'focus_loss_count', 'submission_reason', 'raw_score', 'maximum_score', 'grading_completed_at']);
            $table->unique(['tenant_id', 'cbt_exam_id', 'student_id'], 'uq_cbtsession');
        });
        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->dropForeign('fk_cbtexams_creator');
            $table->dropColumn(['created_by', 'malpractice_enabled', 'focus_loss_policy', 'max_focus_losses', 'require_fullscreen', 'retake_policy', 'strict_marks_validation']);
        });
        Schema::dropIfExists('cbt_exam_section_questions');
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->dropForeign('fk_cbtqs_parent');
            $table->dropIndex('idx_cbtqs_hierarchy');
            $table->dropColumn(['parent_question_id', 'level', 'sequence', 'numbering_style', 'reference_code', 'is_instruction_only', 'requires_answer', 'scoring_method']);
        });
        Schema::dropIfExists('cbt_exam_sections');
    }
};
