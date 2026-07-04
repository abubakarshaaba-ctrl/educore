<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->enum('curriculum_type', ['nerdc', 'british'])->default('nerdc');
            $table->string('topic');
            $table->string('subtopic')->nullable();
            $table->integer('week_number')->nullable();
            $table->date('plan_date')->nullable();
            $table->integer('duration_minutes')->default(40);
            $table->enum('status', ['draft', 'published'])->default('draft');
            // NERDC / TRCN sections (stored as JSON for flexibility)
            $table->text('previous_knowledge')->nullable();
            $table->text('entry_behaviour')->nullable();
            $table->text('behavioural_objectives')->nullable();
            $table->text('instructional_materials')->nullable();
            $table->text('reference_materials')->nullable();
            $table->text('set_induction')->nullable();
            $table->text('presentation')->nullable();
            $table->text('class_activity')->nullable();
            $table->text('evaluation')->nullable();
            $table->text('assignment')->nullable();
            $table->text('conclusion')->nullable();
            // British curriculum extras
            $table->text('learning_objectives')->nullable();
            $table->text('success_criteria')->nullable();
            $table->text('starter_activity')->nullable();
            $table->text('differentiation')->nullable();
            $table->text('plenary')->nullable();
            $table->text('assessment_for_learning')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'teacher_id']);
            $table->index(['tenant_id', 'subject_id', 'class_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
