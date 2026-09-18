<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parallel_curriculum_arm_subject_teachers')) {
            return;
        }

        Schema::create('parallel_curriculum_arm_subject_teachers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parallel_curriculum_class_id');
            $table->unsignedBigInteger('parallel_curriculum_class_arm_id');
            $table->unsignedBigInteger('parallel_curriculum_subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id', 'fk_pc_ast_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parallel_curriculum_class_id', 'fk_pc_ast_class')
                ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
            $table->foreign('parallel_curriculum_class_arm_id', 'fk_pc_ast_arm')
                ->references('id')->on('parallel_curriculum_class_arms')->cascadeOnDelete();
            $table->foreign('parallel_curriculum_subject_id', 'fk_pc_ast_subject')
                ->references('id')->on('parallel_curriculum_subjects')->cascadeOnDelete();
            $table->foreign('teacher_id', 'fk_pc_ast_teacher')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->unique(
                ['parallel_curriculum_class_arm_id', 'parallel_curriculum_subject_id'],
                'uq_pc_arm_subject_teacher'
            );
            $table->index(['tenant_id', 'teacher_id', 'is_active'], 'idx_pc_ast_teacher');
            $table->index(
                ['tenant_id', 'parallel_curriculum_class_id', 'is_active'],
                'idx_pc_ast_class'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_arm_subject_teachers');
    }
};
