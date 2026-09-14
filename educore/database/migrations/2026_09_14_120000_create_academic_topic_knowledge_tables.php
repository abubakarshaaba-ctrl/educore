<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_topics')) {
            Schema::create('academic_topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curriculum_source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
                $table->string('class_label', 120);
                $table->string('subject_label', 160);
                $table->string('term_label', 80);
                $table->unsignedSmallInteger('week_number')->nullable();
                $table->unsignedSmallInteger('lesson_number')->nullable();
                $table->string('topic', 255);
                $table->string('sub_topic', 255)->nullable();
                $table->string('lesson_time', 80)->nullable();
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->unsignedTinyInteger('average_age')->nullable();
                $table->string('sex', 40)->nullable();
                $table->string('resource_type', 80)->default('lesson_note');
                $table->text('entry_behaviour')->nullable();
                $table->text('previous_knowledge')->nullable();
                $table->text('instructional_resources')->nullable();
                $table->text('introduction')->nullable();
                $table->text('reference')->nullable();
                $table->text('student_note_summary')->nullable();
                $table->enum('status', ['draft', 'review', 'approved'])->default('draft');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['class_label', 'subject_label', 'term_label'], 'academic_topics_catalogue_idx');
                $table->index(['subject_label', 'week_number'], 'academic_topics_subject_week_idx');
                $table->index(['status', 'resource_type'], 'academic_topics_status_type_idx');
            });
        }

        if (! Schema::hasTable('academic_topic_blocks')) {
            Schema::create('academic_topic_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_topic_id')->constrained('academic_topics')->cascadeOnDelete();
                $table->string('block_type', 60);
                $table->unsignedSmallInteger('sequence')->default(1);
                $table->string('title', 255)->nullable();
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->timestamps();

                $table->index(['academic_topic_id', 'block_type', 'sequence'], 'academic_topic_blocks_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_topic_blocks');
        Schema::dropIfExists('academic_topics');
    }
};
