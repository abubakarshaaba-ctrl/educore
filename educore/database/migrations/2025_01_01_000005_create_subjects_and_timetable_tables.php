<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'fk_subjects_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code'], 'uq_subjects_tenant_code');
            $table->index(['tenant_id', 'is_active'], 'idx_subjects_tenant_active');
        });

        Schema::create('class_arm_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('session_id');
            $table->foreign('tenant_id', 'fk_armsubjects_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_armsubjects_arm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('subject_id', 'fk_armsubjects_subject')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id', 'fk_armsubjects_teacher')->references('id')->on('users')->nullOnDelete();
            $table->foreign('session_id', 'fk_armsubjects_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id','class_arm_id','subject_id','session_id'], 'uq_armsubject_session');
        });

        Schema::create('timetable_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('session_id');
            $table->foreign('tenant_id', 'fk_timetable_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_timetable_arm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('subject_id', 'fk_timetable_subject')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id', 'fk_timetable_teacher')->references('id')->on('users')->nullOnDelete();
            $table->foreign('session_id', 'fk_timetable_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->enum('day_of_week', ['monday','tuesday','wednesday','thursday','friday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','class_arm_id','day_of_week'], 'idx_timetable_arm_day');
            $table->index(['tenant_id','teacher_id','day_of_week'], 'idx_timetable_teacher_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_periods');
        Schema::dropIfExists('class_arm_subjects');
        Schema::dropIfExists('subjects');
    }
};
