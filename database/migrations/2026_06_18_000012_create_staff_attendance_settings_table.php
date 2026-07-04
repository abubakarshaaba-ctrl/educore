<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base table for per-tenant staff attendance settings.
 *
 * Missing create migration, reconstructed from the StaffAttendanceSetting model.
 * The permanent_qr_secret column is added later by
 * 2026_06_18_000016_add_permanent_qr_to_attendance_settings and is not declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_attendance_settings')) {
            return;
        }

        Schema::create('staff_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->time('resumption_time')->default('08:00:00');
            $table->unsignedSmallInteger('grace_minutes')->default(15);
            $table->time('closing_time')->default('15:00:00');
            $table->decimal('geo_lat', 10, 7)->nullable();
            $table->decimal('geo_lng', 10, 7)->nullable();
            $table->unsignedInteger('geo_radius_meters')->default(100);
            $table->boolean('geo_enabled')->default(false);
            $table->string('qr_secret', 64)->nullable();
            $table->date('qr_secret_date')->nullable();
            $table->timestamps();

            $table->unique('tenant_id', 'uq_staff_att_settings_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_settings');
    }
};
