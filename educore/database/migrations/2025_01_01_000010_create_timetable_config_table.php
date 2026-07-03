<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Timetable Configuration — one record per tenant per session
        // ---------------------------------------------------------------
        Schema::create('timetable_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');

            $table->foreign('tenant_id', 'fk_ttconfig_tenant')
                  ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_ttconfig_session')
                  ->references('id')->on('academic_sessions')->cascadeOnDelete();

            $table->time('school_start')->default('07:30');    // e.g. 07:30
            $table->time('school_end')->default('14:30');      // e.g. 14:30
            $table->unsignedTinyInteger('periods_per_day')->default(8);
            $table->unsignedTinyInteger('period_duration')->default(40); // minutes
            $table->json('breaks')->nullable();
            // e.g. [{"after_period": 3, "duration": 20, "label": "Short Break"},
            //        {"after_period": 6, "duration": 30, "label": "Long Break"}]
            $table->timestamps();

            $table->unique(['tenant_id', 'session_id'], 'uq_ttconfig');
        });

        // ---------------------------------------------------------------
        // Subject Frequency — how many times per week per class
        // ---------------------------------------------------------------
        Schema::create('subject_frequencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('session_id');

            $table->foreign('tenant_id', 'fk_subfreq_tenant')
                  ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_subfreq_classarm')
                  ->references('id')->on('class_arms')->cascadeOnDelete();
            $table->foreign('subject_id', 'fk_subfreq_subject')
                  ->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_subfreq_session')
                  ->references('id')->on('academic_sessions')->cascadeOnDelete();

            $table->unsignedTinyInteger('periods_per_week')->default(2);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'class_arm_id', 'subject_id', 'session_id'],
                'uq_subject_frequency'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_frequencies');
        Schema::dropIfExists('timetable_configs');
    }
};
