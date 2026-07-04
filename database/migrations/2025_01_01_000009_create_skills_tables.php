<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // Skill Definitions
        // ---------------------------------------------------------------
        Schema::create('skill_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'fk_skilldef_tenant')
                  ->references('id')->on('tenants')->cascadeOnDelete();
            $table->enum('category', ['psychomotor', 'affective']);
            $table->string('name');
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'category'], 'idx_skilldef_tenant_cat');
        });

        // ---------------------------------------------------------------
        // Student Skill Ratings
        // ---------------------------------------------------------------
        Schema::create('student_skill_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('skill_definition_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('rated_by')->nullable();

            $table->foreign('tenant_id', 'fk_skillrating_tenant')
                  ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_skillrating_student')
                  ->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('skill_definition_id', 'fk_skillrating_skilldef')
                  ->references('id')->on('skill_definitions')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_skillrating_term')
                  ->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_skillrating_session')
                  ->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->foreign('rated_by', 'fk_skillrating_ratedby')
                  ->references('id')->on('users')->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'student_id', 'skill_definition_id', 'term_id'],
                'uq_skill_rating'
            );
            $table->index(['tenant_id', 'student_id', 'term_id'], 'idx_skillrating_student_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_skill_ratings');
        Schema::dropIfExists('skill_definitions');
    }
};
