<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue of staff clock-ins captured offline (HTTP / no-camera fallback),
 * pending review and processing into staff_attendance_records.
 *
 * Missing create migration, reconstructed from the StaffOfflineClockIn model
 * (table name: staff_offline_clockins). No later migration alters this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_offline_clockins')) {
            return;
        }

        Schema::create('staff_offline_clockins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('clocked_by')->nullable();
            $table->date('attendance_date');
            $table->time('clock_in_time')->nullable();
            $table->string('qr_token', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_staff_offline_tenant_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_offline_clockins');
    }
};
