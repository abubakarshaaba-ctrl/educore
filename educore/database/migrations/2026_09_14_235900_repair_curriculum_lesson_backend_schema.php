<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Final compatibility repair for installations where the older August
        // migrations were already recorded with an incomplete/legacy schema.
        if (Schema::hasTable('curriculum_sources')) {
            $columns = [
                'source_class_level_id' => Schema::hasColumn('curriculum_sources', 'source_class_level_id'),
                'curriculum_level_id' => Schema::hasColumn('curriculum_sources', 'curriculum_level_id'),
                'term_id' => Schema::hasColumn('curriculum_sources', 'term_id'),
                'week_number' => Schema::hasColumn('curriculum_sources', 'week_number'),
                'original_filename' => Schema::hasColumn('curriculum_sources', 'original_filename'),
                'mime_type' => Schema::hasColumn('curriculum_sources', 'mime_type'),
                'file_size' => Schema::hasColumn('curriculum_sources', 'file_size'),
                'raw_text' => Schema::hasColumn('curriculum_sources', 'raw_text'),
                'cleaned_text' => Schema::hasColumn('curriculum_sources', 'cleaned_text'),
                'extraction_status' => Schema::hasColumn('curriculum_sources', 'extraction_status'),
                'index_status' => Schema::hasColumn('curriculum_sources', 'index_status'),
                'needs_review' => Schema::hasColumn('curriculum_sources', 'needs_review'),
                'archived_at' => Schema::hasColumn('curriculum_sources', 'archived_at'),
            ];
            Schema::table('curriculum_sources', function (Blueprint $table) use ($columns) {
                if (! $columns['source_class_level_id']) $table->unsignedBigInteger('source_class_level_id')->nullable();
                if (! $columns['curriculum_level_id']) $table->unsignedBigInteger('curriculum_level_id')->nullable();
                if (! $columns['term_id']) $table->unsignedBigInteger('term_id')->nullable();
                if (! $columns['week_number']) $table->unsignedTinyInteger('week_number')->nullable();
                if (! $columns['original_filename']) $table->string('original_filename')->nullable();
                if (! $columns['mime_type']) $table->string('mime_type', 150)->nullable();
                if (! $columns['file_size']) $table->unsignedBigInteger('file_size')->nullable();
                if (! $columns['raw_text']) $table->longText('raw_text')->nullable();
                if (! $columns['cleaned_text']) $table->longText('cleaned_text')->nullable();
                if (! $columns['extraction_status']) $table->string('extraction_status', 30)->default('pending');
                if (! $columns['index_status']) $table->string('index_status', 30)->default('pending');
                if (! $columns['needs_review']) $table->boolean('needs_review')->default(true);
                if (! $columns['archived_at']) $table->timestamp('archived_at')->nullable();
            });
        }

        if (Schema::hasTable('lesson_plans')) {
            $columns = [
                'lesson_number' => Schema::hasColumn('lesson_plans', 'lesson_number'),
                'lesson_time' => Schema::hasColumn('lesson_plans', 'lesson_time'),
                'average_age' => Schema::hasColumn('lesson_plans', 'average_age'),
                'sex' => Schema::hasColumn('lesson_plans', 'sex'),
                'structured_plan' => Schema::hasColumn('lesson_plans', 'structured_plan'),
                'note_depth' => Schema::hasColumn('lesson_plans', 'note_depth'),
                'approved_at' => Schema::hasColumn('lesson_plans', 'approved_at'),
                'approved_by' => Schema::hasColumn('lesson_plans', 'approved_by'),
                'current_note_revision' => Schema::hasColumn('lesson_plans', 'current_note_revision'),
                'curriculum_level_id' => Schema::hasColumn('lesson_plans', 'curriculum_level_id'),
                'delivery_type' => Schema::hasColumn('lesson_plans', 'delivery_type'),
                'published_at' => Schema::hasColumn('lesson_plans', 'published_at'),
            ];
            Schema::table('lesson_plans', function (Blueprint $table) use ($columns) {
                if (! $columns['lesson_number']) $table->string('lesson_number', 40)->nullable();
                if (! $columns['lesson_time']) $table->string('lesson_time', 40)->nullable();
                if (! $columns['average_age']) $table->string('average_age', 40)->nullable();
                if (! $columns['sex']) $table->string('sex', 30)->nullable();
                if (! $columns['structured_plan']) $table->json('structured_plan')->nullable();
                if (! $columns['note_depth']) $table->string('note_depth', 20)->default('standard');
                if (! $columns['approved_at']) $table->timestamp('approved_at')->nullable();
                if (! $columns['approved_by']) $table->unsignedBigInteger('approved_by')->nullable();
                if (! $columns['current_note_revision']) $table->unsignedInteger('current_note_revision')->nullable();
                if (! $columns['curriculum_level_id']) $table->unsignedBigInteger('curriculum_level_id')->nullable();
                if (! $columns['delivery_type']) $table->string('delivery_type', 30)->default('regular');
                if (! $columns['published_at']) $table->timestamp('published_at')->nullable();
            });
        }

        if (! Schema::hasTable('lesson_note_revisions')) {
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
            });
        }

        if (! Schema::hasTable('lesson_note_validations')) {
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
            });
        }

        if (! Schema::hasTable('ai_usage_logs')) {
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
            });
        }
    }

    public function down(): void
    {
        // Production repair migration: never remove repaired schema/data.
    }
};