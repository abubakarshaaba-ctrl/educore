<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_attendance_working_days')) {
            Schema::create('staff_attendance_working_days', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('day_of_week', 12);
                $table->boolean('is_working')->default(false);
                $table->time('resumption_time')->nullable();
                $table->time('closing_time')->nullable();
                $table->unsignedSmallInteger('grace_minutes')->default(15);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_staff_att_wd_tenant')
                    ->references('id')->on('tenants')->cascadeOnDelete();

                $table->unique(
                    ['tenant_id', 'day_of_week'],
                    'uq_staff_att_working_day'
                );
            });
        }

        if (! Schema::hasTable('staff_attendance_records')) {
            return;
        }

        if (! Schema::hasColumn('staff_attendance_records', 'expected_resumption_time')) {
            Schema::table('staff_attendance_records', function (Blueprint $table): void {
                $table->time('expected_resumption_time')->nullable()->after('clock_in_time');
            });
        }

        if (! Schema::hasColumn('staff_attendance_records', 'expected_closing_time')) {
            Schema::table('staff_attendance_records', function (Blueprint $table): void {
                $table->time('expected_closing_time')->nullable()->after('expected_resumption_time');
            });
        }

        if (! Schema::hasColumn('staff_attendance_records', 'grace_minutes')) {
            Schema::table('staff_attendance_records', function (Blueprint $table): void {
                $table->unsignedSmallInteger('grace_minutes')->nullable()->after('expected_closing_time');
            });
        }

        if (! Schema::hasColumn('staff_attendance_records', 'departure_status')) {
            Schema::table('staff_attendance_records', function (Blueprint $table): void {
                $table->string('departure_status', 20)->nullable()->after('clock_out_time');
            });
        }

        if (! Schema::hasColumn('staff_attendance_records', 'scheduled_workday')) {
            Schema::table('staff_attendance_records', function (Blueprint $table): void {
                $table->boolean('scheduled_workday')->nullable()->after('departure_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_attendance_records')) {
            foreach ([
                'scheduled_workday',
                'departure_status',
                'grace_minutes',
                'expected_closing_time',
                'expected_resumption_time',
            ] as $column) {
                if (Schema::hasColumn('staff_attendance_records', $column)) {
                    Schema::table('staff_attendance_records', function (Blueprint $table) use ($column): void {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        Schema::dropIfExists('staff_attendance_working_days');
    }
};
