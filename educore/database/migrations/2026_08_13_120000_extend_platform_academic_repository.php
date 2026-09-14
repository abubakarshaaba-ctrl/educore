<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This migration may be retried after an interrupted shared-hosting
        // deployment. Add/create only what is still missing so Laravel can mark
        // the migration complete and continue to the later repair migrations.
        if (Schema::hasTable('lesson_plans')) {
            $lessonColumns = [
                'curriculum_level_id' => Schema::hasColumn('lesson_plans', 'curriculum_level_id'),
                'delivery_type' => Schema::hasColumn('lesson_plans', 'delivery_type'),
                'published_at' => Schema::hasColumn('lesson_plans', 'published_at'),
            ];
            Schema::table('lesson_plans', function (Blueprint $table) use ($lessonColumns) {
                if (! $lessonColumns['curriculum_level_id']) $table->unsignedBigInteger('curriculum_level_id')->nullable();
                if (! $lessonColumns['delivery_type']) $table->string('delivery_type', 30)->default('regular');
                if (! $lessonColumns['published_at']) $table->timestamp('published_at')->nullable();
            });
        }

        if (Schema::hasTable('curriculum_sources')) {
            $sourceColumns = [
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
            Schema::table('curriculum_sources', function (Blueprint $table) use ($sourceColumns) {
                if (! $sourceColumns['source_class_level_id']) $table->unsignedBigInteger('source_class_level_id')->nullable();
                if (! $sourceColumns['curriculum_level_id']) $table->unsignedBigInteger('curriculum_level_id')->nullable();
                if (! $sourceColumns['term_id']) $table->unsignedBigInteger('term_id')->nullable();
                if (! $sourceColumns['week_number']) $table->unsignedTinyInteger('week_number')->nullable();
                if (! $sourceColumns['original_filename']) $table->string('original_filename')->nullable();
                if (! $sourceColumns['mime_type']) $table->string('mime_type', 150)->nullable();
                if (! $sourceColumns['file_size']) $table->unsignedBigInteger('file_size')->nullable();
                if (! $sourceColumns['raw_text']) $table->longText('raw_text')->nullable();
                if (! $sourceColumns['cleaned_text']) $table->longText('cleaned_text')->nullable();
                if (! $sourceColumns['extraction_status']) $table->string('extraction_status', 30)->default('pending');
                if (! $sourceColumns['index_status']) $table->string('index_status', 30)->default('pending');
                if (! $sourceColumns['needs_review']) $table->boolean('needs_review')->default(true);
                if (! $sourceColumns['archived_at']) $table->timestamp('archived_at')->nullable();
            });
        }

        if (! Schema::hasTable('curriculum_topics')) {
            Schema::create('curriculum_topics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('curriculum_level_id');
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedTinyInteger('recommended_week')->nullable();
                $table->string('topic');
                $table->json('subtopics')->nullable();
                $table->json('learning_objectives')->nullable();
                $table->json('keywords')->nullable();
                $table->string('curriculum_source')->nullable();
                $table->string('curriculum_year', 20)->nullable();
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
                $table->index(['subject_id', 'curriculum_level_id', 'status']);
            });
        }

        if (! Schema::hasTable('repository_imports')) {
            Schema::create('repository_imports', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('format', 10);
                $table->unsignedBigInteger('uploaded_by');
                $table->string('status', 30)->default('queued');
                foreach (['discovered', 'imported', 'duplicates', 'failed', 'needs_review'] as $column) {
                    $table->unsignedInteger($column)->default(0);
                }
                $table->json('mapping')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('repository_import_items')) {
            Schema::create('repository_import_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('repository_import_id');
                $table->unsignedBigInteger('curriculum_source_id')->nullable();
                $table->string('relative_path')->nullable();
                $table->string('status', 30);
                $table->string('message')->nullable();
                $table->json('inferred_metadata')->nullable();
                $table->timestamps();
                $table->index(['repository_import_id', 'status']);
            });
        }

        if (! Schema::hasTable('lesson_plan_sources')) {
            Schema::create('lesson_plan_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_plan_id');
                $table->unsignedBigInteger('curriculum_source_id');
                $table->unsignedBigInteger('curriculum_fragment_id')->nullable();
                $table->decimal('relevance_score', 7, 4)->nullable();
                $table->unsignedSmallInteger('rank')->nullable();
                $table->string('generation_type', 30);
                $table->timestamps();
                $table->index(['lesson_plan_id', 'generation_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_sources');
        Schema::dropIfExists('repository_import_items');
        Schema::dropIfExists('repository_imports');
        Schema::dropIfExists('curriculum_topics');
    }
};