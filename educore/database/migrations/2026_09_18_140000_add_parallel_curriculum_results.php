<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parallel_curriculum_grades')) {
            Schema::create('parallel_curriculum_grades', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->string('grade_letter', 20);
                $table->decimal('min_score', 6, 2);
                $table->decimal('max_score', 6, 2);
                $table->string('remark', 100)->nullable();
                $table->boolean('is_pass_grade')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_grade_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_grade_curriculum')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();

                $table->unique(['parallel_curriculum_id', 'grade_letter'], 'uq_pc_grade_letter');
                $table->index(
                    ['tenant_id', 'parallel_curriculum_id', 'min_score', 'max_score'],
                    'idx_pc_grade_range'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_report_publications')) {
            Schema::create('parallel_curriculum_report_publications', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->unsignedBigInteger('term_id');
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('published_by')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('unpublished_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_pub_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_pub_curriculum')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_pub_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('term_id', 'fk_pc_pub_term')
                    ->references('id')->on('terms')->cascadeOnDelete();
                $table->foreign('published_by', 'fk_pc_pub_user')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(['parallel_curriculum_class_id', 'term_id'], 'uq_pc_pub_class_term');
                $table->index(['tenant_id', 'status', 'term_id'], 'idx_pc_pub_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_report_publications');
        Schema::dropIfExists('parallel_curriculum_grades');
    }
};
