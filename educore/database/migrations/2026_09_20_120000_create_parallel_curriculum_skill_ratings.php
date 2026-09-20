<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parallel_curriculum_skill_ratings')) {
            return;
        }

        Schema::create('parallel_curriculum_skill_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parallel_curriculum_enrolment_id')->index();
            $table->unsignedBigInteger('parallel_curriculum_id')->index();
            $table->unsignedBigInteger('parallel_curriculum_class_id')->index();
            $table->unsignedBigInteger('parallel_curriculum_class_arm_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('skill_definition_id')->index();
            $table->unsignedBigInteger('term_id')->index();
            $table->unsignedBigInteger('session_id')->index();
            $table->unsignedTinyInteger('rating');
            $table->unsignedBigInteger('rated_by')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['parallel_curriculum_enrolment_id', 'skill_definition_id', 'term_id'],
                'pc_skill_rating_enrol_skill_term_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_skill_ratings');
    }
};
