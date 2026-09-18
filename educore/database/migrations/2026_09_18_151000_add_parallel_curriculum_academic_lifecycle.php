<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parallel_curriculum_class_arms')) {
            Schema::create('parallel_curriculum_class_arms', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->string('name', 80);
                $table->string('code', 40)->nullable();
                $table->unsignedSmallInteger('capacity')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_arm_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_arm_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();

                $table->unique(
                    ['parallel_curriculum_class_id', 'name'],
                    'uq_pc_arm_class_name'
                );
                $table->index(
                    ['tenant_id', 'parallel_curriculum_class_id', 'is_active'],
                    'idx_pc_arm_active'
                );
            });
        }

        if (
            Schema::hasTable('parallel_curriculum_enrolments')
            && ! Schema::hasColumn('parallel_curriculum_enrolments', 'parallel_curriculum_class_arm_id')
        ) {
            Schema::table('parallel_curriculum_enrolments', function (Blueprint $table): void {
                $table->unsignedBigInteger('parallel_curriculum_class_arm_id')
                    ->nullable()
                    ->after('parallel_curriculum_class_id');

                $table->foreign(
                    'parallel_curriculum_class_arm_id',
                    'fk_pc_enrol_arm'
                )->references('id')->on('parallel_curriculum_class_arms')->nullOnDelete();

                $table->index(
                    ['tenant_id', 'parallel_curriculum_class_arm_id', 'session_id', 'is_active'],
                    'idx_pc_enrol_arm_session'
                );
            });
        }

        // Backward-compatible arm bootstrap: every pre-existing parallel class
        // receives one default arm, then its historical enrolments are attached
        // to that arm. New schools can rename it or add further arms.
        if (
            Schema::hasTable('parallel_curriculum_classes')
            && Schema::hasTable('parallel_curriculum_class_arms')
        ) {
            $classes = DB::table('parallel_curriculum_classes')
                ->select('id', 'tenant_id')
                ->orderBy('id')
                ->get();

            foreach ($classes as $class) {
                $armId = DB::table('parallel_curriculum_class_arms')
                    ->where('parallel_curriculum_class_id', $class->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->value('id');

                if (! $armId) {
                    $armId = DB::table('parallel_curriculum_class_arms')->insertGetId([
                        'tenant_id' => $class->tenant_id,
                        'parallel_curriculum_class_id' => $class->id,
                        'name' => 'A',
                        'code' => 'A',
                        'capacity' => null,
                        'sort_order' => 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (
                    Schema::hasTable('parallel_curriculum_enrolments')
                    && Schema::hasColumn(
                        'parallel_curriculum_enrolments',
                        'parallel_curriculum_class_arm_id'
                    )
                ) {
                    DB::table('parallel_curriculum_enrolments')
                        ->where('parallel_curriculum_class_id', $class->id)
                        ->whereNull('parallel_curriculum_class_arm_id')
                        ->update([
                            'parallel_curriculum_class_arm_id' => $armId,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        if (! Schema::hasTable('parallel_curriculum_class_grades')) {
            Schema::create('parallel_curriculum_class_grades', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('parallel_curriculum_class_id');
                $table->string('grade_letter', 20);
                $table->decimal('min_score', 6, 2);
                $table->decimal('max_score', 6, 2);
                $table->string('remark', 100)->nullable();
                $table->boolean('is_pass_grade')->default(true);
                $table->decimal('grade_point', 6, 2)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_cgrade_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_cgrade_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_class_id', 'fk_pc_cgrade_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();

                $table->unique(
                    ['parallel_curriculum_class_id', 'grade_letter'],
                    'uq_pc_cgrade_letter'
                );
                $table->index(
                    ['tenant_id', 'parallel_curriculum_class_id', 'min_score', 'max_score'],
                    'idx_pc_cgrade_range'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_promotion_rules')) {
            Schema::create('parallel_curriculum_promotion_rules', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('source_class_id');
                $table->unsignedBigInteger('destination_class_id')->nullable();
                $table->decimal('minimum_average', 6, 2)->default(50);
                $table->unsignedSmallInteger('max_failed_subjects')->default(2);
                $table->boolean('require_complete_result')->default(true);
                $table->string('failure_action', 20)->default('repeat');
                $table->string('arm_strategy', 30)->default('same_name');
                $table->boolean('is_terminal')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_prule_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_prule_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('source_class_id', 'fk_pc_prule_source')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('destination_class_id', 'fk_pc_prule_dest')
                    ->references('id')->on('parallel_curriculum_classes')->nullOnDelete();

                $table->unique('source_class_id', 'uq_pc_prule_source');
                $table->index(
                    ['tenant_id', 'parallel_curriculum_id', 'is_active'],
                    'idx_pc_prule_active'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_promotions')) {
            Schema::create('parallel_curriculum_promotions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('source_enrolment_id');
                $table->unsignedBigInteger('source_session_id');
                $table->unsignedBigInteger('target_session_id');
                $table->unsignedBigInteger('source_class_id');
                $table->unsignedBigInteger('source_arm_id')->nullable();
                $table->unsignedBigInteger('destination_class_id')->nullable();
                $table->unsignedBigInteger('destination_arm_id')->nullable();
                $table->string('decision', 20);
                $table->decimal('average_score', 6, 2)->nullable();
                $table->unsignedSmallInteger('failed_subjects')->default(0);
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_promo_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_promo_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_pc_promo_student')
                    ->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('source_enrolment_id', 'fk_pc_promo_enrol')
                    ->references('id')->on('parallel_curriculum_enrolments')->cascadeOnDelete();
                $table->foreign('source_session_id', 'fk_pc_promo_source_session')
                    ->references('id')->on('academic_sessions')->cascadeOnDelete();
                $table->foreign('target_session_id', 'fk_pc_promo_target_session')
                    ->references('id')->on('academic_sessions')->cascadeOnDelete();
                $table->foreign('source_class_id', 'fk_pc_promo_source_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('source_arm_id', 'fk_pc_promo_source_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->nullOnDelete();
                $table->foreign('destination_class_id', 'fk_pc_promo_dest_class')
                    ->references('id')->on('parallel_curriculum_classes')->nullOnDelete();
                $table->foreign('destination_arm_id', 'fk_pc_promo_dest_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->nullOnDelete();
                $table->foreign('processed_by', 'fk_pc_promo_user')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(
                    ['source_enrolment_id', 'target_session_id'],
                    'uq_pc_promo_enrol_target'
                );
                $table->index(
                    ['tenant_id', 'parallel_curriculum_id', 'target_session_id', 'decision'],
                    'idx_pc_promo_target'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_transfers')) {
            Schema::create('parallel_curriculum_transfers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('enrolment_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('from_class_id');
                $table->unsignedBigInteger('from_arm_id')->nullable();
                $table->unsignedBigInteger('to_class_id');
                $table->unsignedBigInteger('to_arm_id')->nullable();
                $table->string('movement_type', 20);
                $table->text('reason');
                $table->date('effective_date')->nullable();
                $table->string('status', 20)->default('completed');
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_transfer_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_transfer_curr')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_pc_transfer_student')
                    ->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('enrolment_id', 'fk_pc_transfer_enrol')
                    ->references('id')->on('parallel_curriculum_enrolments')->cascadeOnDelete();
                $table->foreign('session_id', 'fk_pc_transfer_session')
                    ->references('id')->on('academic_sessions')->cascadeOnDelete();
                $table->foreign('from_class_id', 'fk_pc_transfer_from_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('from_arm_id', 'fk_pc_transfer_from_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->nullOnDelete();
                $table->foreign('to_class_id', 'fk_pc_transfer_to_class')
                    ->references('id')->on('parallel_curriculum_classes')->cascadeOnDelete();
                $table->foreign('to_arm_id', 'fk_pc_transfer_to_arm')
                    ->references('id')->on('parallel_curriculum_class_arms')->nullOnDelete();
                $table->foreign('processed_by', 'fk_pc_transfer_user')
                    ->references('id')->on('users')->nullOnDelete();

                $table->index(
                    ['tenant_id', 'student_id', 'session_id'],
                    'idx_pc_transfer_student_session'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_transfers');
        Schema::dropIfExists('parallel_curriculum_promotions');
        Schema::dropIfExists('parallel_curriculum_promotion_rules');
        Schema::dropIfExists('parallel_curriculum_class_grades');

        if (
            Schema::hasTable('parallel_curriculum_enrolments')
            && Schema::hasColumn('parallel_curriculum_enrolments', 'parallel_curriculum_class_arm_id')
        ) {
            Schema::table('parallel_curriculum_enrolments', function (Blueprint $table): void {
                $table->dropForeign('fk_pc_enrol_arm');
                $table->dropIndex('idx_pc_enrol_arm_session');
                $table->dropColumn('parallel_curriculum_class_arm_id');
            });
        }

        Schema::dropIfExists('parallel_curriculum_class_arms');
    }
};
