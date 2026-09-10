<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table): void {
            if (!Schema::hasColumn('staff_attendance_records', 'client_uuid')) {
                $table->uuid('client_uuid')->nullable()->after('is_offline_upload');
                $table->unique(['tenant_id', 'client_uuid'], 'staff_attendance_client_uuid_unique');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'location_accuracy')) {
                $table->decimal('location_accuracy', 8, 2)->nullable()->after('clock_in_lng');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_reason')) {
                $table->text('proxy_reason')->nullable()->after('proxy_review_status');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_actor_ip')) {
                $table->string('proxy_actor_ip', 64)->nullable()->after('proxy_reason');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_device')) {
                $table->string('proxy_device', 255)->nullable()->after('proxy_actor_ip');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'offline_rejection_reason')) {
                $table->text('offline_rejection_reason')->nullable()->after('is_offline_upload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table): void {
            if (Schema::hasColumn('staff_attendance_records', 'client_uuid')) {
                $table->dropUnique('staff_attendance_client_uuid_unique');
                $table->dropColumn('client_uuid');
            }
            foreach (['location_accuracy', 'proxy_reason', 'proxy_actor_ip', 'proxy_device', 'offline_rejection_reason'] as $column) {
                if (Schema::hasColumn('staff_attendance_records', $column)) $table->dropColumn($column);
            }
        });
    }
};
