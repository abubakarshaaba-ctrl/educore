<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('excluded_weekdays')->nullable(); // e.g. [0,6] = Sun, Sat
            $table->enum('status', ['draft', 'timetabled', 'supervision_planned', 'published'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_period_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60); // e.g. Morning, Afternoon
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('exam_period_class_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_level_id')->constrained()->cascadeOnDelete();
            $table->unique(['exam_period_id', 'class_level_id'], 'epc_unique');
        });

        Schema::create('exam_period_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['exam_period_id', 'user_id'], 'eps_unique');
        });

        Schema::create('exam_timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('exam_date');
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('venue', 100)->nullable();
            $table->timestamps();

            $table->unique(['exam_period_id', 'class_level_id', 'exam_date', 'exam_session_id'], 'ete_slot_unique');
        });

        Schema::create('exam_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_timetable_entry_id')->constrained('exam_timetable_entries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_timetable_entry_id', 'user_id'], 'esup_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_supervisors');
        Schema::dropIfExists('exam_timetable_entries');
        Schema::dropIfExists('exam_period_staff');
        Schema::dropIfExists('exam_period_class_levels');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('exam_periods');
    }
};
