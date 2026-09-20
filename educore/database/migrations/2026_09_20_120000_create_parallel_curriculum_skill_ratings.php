<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * MySQL limits identifier names to 64 characters. The first version of
         * this migration relied on Laravel's generated index names, and the
         * enrolment index exceeded that limit. A failed MySQL DDL migration can
         * leave the table behind even though Laravel did not record the
         * migration as completed. If up() is running and the table already
         * exists, it is therefore the incomplete table from that failed run.
         */
        Schema::dropIfExists('parallel_curriculum_skill_ratings');

        Schema::create('parallel_curriculum_skill_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parallel_curriculum_enrolment_id');
            $table->unsignedBigInteger('parallel_curriculum_id');
            $table->unsignedBigInteger('parallel_curriculum_class_id');
            $table->unsignedBigInteger('parallel_curriculum_class_arm_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('skill_definition_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedTinyInteger('rating');
            $table->unsignedBigInteger('rated_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'pcsr_tenant_idx');
            $table->index(
                'parallel_curriculum_enrolment_id',
                'pcsr_enrolment_idx'
            );
            $table->index(
                'parallel_curriculum_id',
                'pcsr_curriculum_idx'
            );
            $table->index(
                'parallel_curriculum_class_id',
                'pcsr_class_idx'
            );
            $table->index(
                'parallel_curriculum_class_arm_id',
                'pcsr_arm_idx'
            );
            $table->index('student_id', 'pcsr_student_idx');
            $table->index('skill_definition_id', 'pcsr_skill_idx');
            $table->index('term_id', 'pcsr_term_idx');
            $table->index('session_id', 'pcsr_session_idx');
            $table->index('rated_by', 'pcsr_rated_by_idx');

            $table->unique(
                [
                    'parallel_curriculum_enrolment_id',
                    'skill_definition_id',
                    'term_id',
                ],
                'pcsr_enrol_skill_term_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_skill_ratings');
    }
};
