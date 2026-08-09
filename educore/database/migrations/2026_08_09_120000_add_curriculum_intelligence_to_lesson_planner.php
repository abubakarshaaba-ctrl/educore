<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->string('lesson_number', 40)->nullable()->after('week_number');
            $table->string('lesson_time', 40)->nullable()->after('lesson_number');
            $table->string('average_age', 40)->nullable()->after('lesson_time');
            $table->string('sex', 30)->nullable()->after('average_age');
            $table->json('structured_plan')->nullable()->after('lesson_notes');
            $table->string('note_depth', 20)->default('standard')->after('structured_plan');
            $table->timestamp('approved_at')->nullable()->after('note_depth');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->unsignedInteger('current_note_revision')->nullable()->after('approved_by');
            $table->index(['tenant_id', 'status', 'approved_at'], 'lesson_plan_approval_idx');
        });

        Schema::create('curriculum_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('authority', 40);
            $table->string('source_type', 50);
            $table->string('title');
            $table->string('education_level', 80)->nullable();
            $table->string('version', 80);
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_file_path')->nullable();
            $table->string('source_url')->nullable();
            $table->char('checksum', 64)->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_active')->default(false);
            $table->string('review_status', 30)->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'is_active', 'authority'], 'curriculum_source_scope_idx');
            $table->index(['subject_id', 'class_level_id', 'effective_from'], 'curriculum_source_lookup_idx');
        });

        Schema::create('curriculum_fragments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curriculum_source_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('theme')->nullable();
            $table->string('topic');
            $table->string('subtopic')->nullable();
            $table->text('content');
            $table->text('learning_expectation')->nullable();
            $table->string('source_locator')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('curriculum_source_id')->references('id')->on('curriculum_sources')->cascadeOnDelete();
            $table->index(['subject_id', 'class_level_id', 'topic'], 'curriculum_fragment_lookup_idx');
        });

        Schema::create('lesson_note_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lesson_plan_id');
            $table->unsignedInteger('revision');
            $table->string('status', 30)->default('draft');
            $table->string('depth', 20)->default('standard');
            $table->json('content');
            $table->json('source_trace')->nullable();
            $table->boolean('ai_generated')->default(true);
            $table->boolean('teacher_edited')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['lesson_plan_id', 'revision']);
            $table->index(['tenant_id', 'status', 'created_at'], 'lesson_note_scope_idx');
        });

        Schema::create('lesson_note_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lesson_plan_id');
            $table->unsignedBigInteger('lesson_note_revision_id');
            $table->string('status', 20);
            $table->string('plan_coverage', 30);
            $table->json('authority_alignment')->nullable();
            $table->json('missing_plan_items')->nullable();
            $table->json('missing_curriculum_items')->nullable();
            $table->json('factual_concerns')->nullable();
            $table->json('suggested_additions')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'lesson_plan_id'], 'lesson_validation_scope_idx');
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('lesson_plan_id')->nullable();
            $table->unsignedBigInteger('lesson_note_revision_id')->nullable();
            $table->string('feature', 60);
            $table->string('provider', 40);
            $table->string('model', 100)->nullable();
            $table->string('request_type', 60);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at'], 'ai_usage_tenant_idx');
            $table->index(['feature', 'provider', 'created_at'], 'ai_usage_platform_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('lesson_note_validations');
        Schema::dropIfExists('lesson_note_revisions');
        Schema::dropIfExists('curriculum_fragments');
        Schema::dropIfExists('curriculum_sources');
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropIndex('lesson_plan_approval_idx');
            $table->dropColumn(['lesson_number','lesson_time','average_age','sex','structured_plan', 'note_depth', 'approved_at', 'approved_by', 'current_note_revision']);
        });
    }
};
