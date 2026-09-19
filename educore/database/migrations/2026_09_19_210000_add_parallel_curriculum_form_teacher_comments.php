<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parallel_curriculum_result_comments')) {
            return;
        }

        Schema::create('parallel_curriculum_result_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parallel_curriculum_enrolment_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('form_teacher_id')->nullable();
            $table->text('form_teacher_comment')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id', 'fk_pc_comment_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parallel_curriculum_enrolment_id', 'fk_pc_comment_enrolment')
                ->references('id')->on('parallel_curriculum_enrolments')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_pc_comment_term')
                ->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('form_teacher_id', 'fk_pc_comment_teacher')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['parallel_curriculum_enrolment_id', 'term_id'],
                'uq_pc_comment_enrolment_term'
            );
            $table->index(['tenant_id', 'term_id'], 'idx_pc_comment_tenant_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_result_comments');
    }
};
