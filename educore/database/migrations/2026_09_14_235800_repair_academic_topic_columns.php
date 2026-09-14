<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_topics')) {
            return;
        }

        $required = [
            'curriculum_source_id' => Schema::hasColumn('academic_topics', 'curriculum_source_id'),
            'class_label' => Schema::hasColumn('academic_topics', 'class_label'),
            'subject_label' => Schema::hasColumn('academic_topics', 'subject_label'),
            'term_label' => Schema::hasColumn('academic_topics', 'term_label'),
            'week_number' => Schema::hasColumn('academic_topics', 'week_number'),
            'lesson_number' => Schema::hasColumn('academic_topics', 'lesson_number'),
            'topic' => Schema::hasColumn('academic_topics', 'topic'),
            'sub_topic' => Schema::hasColumn('academic_topics', 'sub_topic'),
            'lesson_time' => Schema::hasColumn('academic_topics', 'lesson_time'),
            'duration_minutes' => Schema::hasColumn('academic_topics', 'duration_minutes'),
            'average_age' => Schema::hasColumn('academic_topics', 'average_age'),
            'sex' => Schema::hasColumn('academic_topics', 'sex'),
            'resource_type' => Schema::hasColumn('academic_topics', 'resource_type'),
            'entry_behaviour' => Schema::hasColumn('academic_topics', 'entry_behaviour'),
            'previous_knowledge' => Schema::hasColumn('academic_topics', 'previous_knowledge'),
            'instructional_resources' => Schema::hasColumn('academic_topics', 'instructional_resources'),
            'introduction' => Schema::hasColumn('academic_topics', 'introduction'),
            'reference' => Schema::hasColumn('academic_topics', 'reference'),
            'student_note_summary' => Schema::hasColumn('academic_topics', 'student_note_summary'),
            'status' => Schema::hasColumn('academic_topics', 'status'),
            'reviewed_by' => Schema::hasColumn('academic_topics', 'reviewed_by'),
            'reviewed_at' => Schema::hasColumn('academic_topics', 'reviewed_at'),
            'created_at' => Schema::hasColumn('academic_topics', 'created_at'),
            'updated_at' => Schema::hasColumn('academic_topics', 'updated_at'),
        ];

        Schema::table('academic_topics', function (Blueprint $table) use ($required) {
            if (! $required['curriculum_source_id']) $table->unsignedBigInteger('curriculum_source_id')->nullable();
            if (! $required['class_label']) $table->string('class_label', 120)->nullable();
            if (! $required['subject_label']) $table->string('subject_label', 160)->nullable();
            if (! $required['term_label']) $table->string('term_label', 80)->nullable();
            if (! $required['week_number']) $table->unsignedSmallInteger('week_number')->nullable();
            if (! $required['lesson_number']) $table->unsignedSmallInteger('lesson_number')->nullable();
            if (! $required['topic']) $table->string('topic', 255)->nullable();
            if (! $required['sub_topic']) $table->string('sub_topic', 255)->nullable();
            if (! $required['lesson_time']) $table->string('lesson_time', 80)->nullable();
            if (! $required['duration_minutes']) $table->unsignedSmallInteger('duration_minutes')->nullable();
            if (! $required['average_age']) $table->unsignedTinyInteger('average_age')->nullable();
            if (! $required['sex']) $table->string('sex', 40)->nullable();
            if (! $required['resource_type']) $table->string('resource_type', 80)->default('lesson_note');
            if (! $required['entry_behaviour']) $table->text('entry_behaviour')->nullable();
            if (! $required['previous_knowledge']) $table->text('previous_knowledge')->nullable();
            if (! $required['instructional_resources']) $table->text('instructional_resources')->nullable();
            if (! $required['introduction']) $table->text('introduction')->nullable();
            if (! $required['reference']) $table->text('reference')->nullable();
            if (! $required['student_note_summary']) $table->text('student_note_summary')->nullable();
            if (! $required['status']) $table->string('status', 20)->default('draft');
            if (! $required['reviewed_by']) $table->unsignedBigInteger('reviewed_by')->nullable();
            if (! $required['reviewed_at']) $table->timestamp('reviewed_at')->nullable();
            if (! $required['created_at']) $table->timestamp('created_at')->nullable();
            if (! $required['updated_at']) $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        // Compatibility repair: never remove restored production columns.
    }
};
