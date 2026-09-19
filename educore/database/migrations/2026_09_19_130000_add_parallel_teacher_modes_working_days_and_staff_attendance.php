<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parallel_curriculum_class_arms')) {
            if (! Schema::hasColumn('parallel_curriculum_class_arms', 'teaching_assignment_mode')) {
                Schema::table('parallel_curriculum_class_arms', function (Blueprint $table): void {
                    $table->string('teaching_assignment_mode', 30)
                        ->default('subject_based')
                        ->after('capacity');
                });
            }

            if (! Schema::hasColumn('parallel_curriculum_class_arms', 'class_teacher_id')) {
                Schema::table('parallel_curriculum_class_arms', function (Blueprint $table): void {
                    $table->unsignedBigInteger('class_teacher_id')
                        ->nullable()
                        ->after('teaching_assignment_mode');

                    $table->foreign('class_teacher_id', 'fk_pc_arm_class_teacher')
                        ->references('id')->on('users')->nullOnDelete();

                    $table->index(
                        ['tenant_id', 'class_teacher_id'],
                        'idx_pc_arm_class_teacher'
                    );
                });
            }
        }

        // MySQL ENUM needs to be widened explicitly. SQLite represents Laravel
        // enum columns as text and therefore needs no alteration.
        if (
            Schema::hasTable('parallel_curriculum_timetable_periods')
            && DB::getDriverName() === 'mysql'
        ) {
            DB::statement(
                "ALTER TABLE parallel_curriculum_timetable_periods MODIFY day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL"
            );
        }

        if (! Schema::hasTable('parallel_curriculum_working_days')) {
            Schema::create('parallel_curriculum_working_days', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->string('day_of_week', 12);
                $table->boolean('is_working')->default(false);
                $table->time('resumption_time')->nullable();
                $table->time('closing_time')->nullable();
                $table->unsignedSmallInteger('grace_minutes')->default(15);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_wd_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_wd_curriculum')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();

                $table->unique(
                    ['tenant_id', 'parallel_curriculum_id', 'day_of_week'],
                    'uq_pc_working_day'
                );
            });
        }

        if (! Schema::hasTable('parallel_curriculum_staff_attendance_records')) {
            Schema::create('parallel_curriculum_staff_attendance_records', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->unsignedBigInteger('user_id');
                $table->date('attendance_date');
                $table->string('status', 20)->nullable();
                $table->string('departure_status', 20)->nullable();
                $table->time('clock_in_time')->nullable();
                $table->time('clock_out_time')->nullable();

                // Snapshot the applicable day schedule so historical records
                // remain auditable when the programme timetable later changes.
                $table->time('expected_resumption_time')->nullable();
                $table->time('expected_closing_time')->nullable();
                $table->unsignedSmallInteger('grace_minutes')->default(0);

                $table->string('clock_in_method', 30)->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_staff_att_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_staff_att_curriculum')
                    ->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->foreign('user_id', 'fk_pc_staff_att_user')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('recorded_by', 'fk_pc_staff_att_recorder')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(
                    ['tenant_id', 'parallel_curriculum_id', 'user_id', 'attendance_date'],
                    'uq_pc_staff_attendance'
                );
                $table->index(
                    ['tenant_id', 'parallel_curriculum_id', 'attendance_date'],
                    'idx_pc_staff_att_date'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parallel_curriculum_staff_attendance_records');
        Schema::dropIfExists('parallel_curriculum_working_days');

        if (
            Schema::hasTable('parallel_curriculum_class_arms')
            && Schema::hasColumn('parallel_curriculum_class_arms', 'class_teacher_id')
        ) {
            Schema::table('parallel_curriculum_class_arms', function (Blueprint $table): void {
                $table->dropForeign('fk_pc_arm_class_teacher');
                $table->dropIndex('idx_pc_arm_class_teacher');
                $table->dropColumn('class_teacher_id');
            });
        }

        if (
            Schema::hasTable('parallel_curriculum_class_arms')
            && Schema::hasColumn('parallel_curriculum_class_arms', 'teaching_assignment_mode')
        ) {
            Schema::table('parallel_curriculum_class_arms', function (Blueprint $table): void {
                $table->dropColumn('teaching_assignment_mode');
            });
        }
    }
};
