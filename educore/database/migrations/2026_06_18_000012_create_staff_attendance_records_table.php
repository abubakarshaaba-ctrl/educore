<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base table for staff digital attendance.
 *
 * NOTE: This create migration was missing from the repository — the table had
 * only ever been built manually in dev, while later add_* migrations assumed it
 * existed. The guard makes it safe to run against an environment where the table
 * was already created by hand. Columns added by later migrations
 * (proxy_verified, proxy_pin_used — 000013; clock_in_photo, proxy_photo — 000014)
 * are intentionally NOT declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_attendance_records')) {
            return;
        }

        Schema::create('staff_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');
            $table->string('status', 20)->default('present');
            $table->time('clock_in_time')->nullable();
            $table->time('clock_out_time')->nullable();
            $table->string('clock_in_method', 30)->nullable();
            $table->unsignedBigInteger('clocked_in_by')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->boolean('geo_verified')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_offline_upload')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'attendance_date'], 'idx_staff_att_tenant_date');
            $table->index(['user_id', 'attendance_date'], 'idx_staff_att_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_records');
    }
};
