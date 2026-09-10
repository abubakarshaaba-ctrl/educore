<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $missing = [];
        foreach (['client_uuid','location_accuracy','proxy_reason','proxy_actor_ip','proxy_device','offline_rejection_reason'] as $column) {
            if (! Schema::hasColumn('staff_attendance_records', $column)) $missing[] = $column;
        }

        if ($missing) {
            Schema::table('staff_attendance_records', function (Blueprint $table) use ($missing): void {
                if (in_array('client_uuid', $missing, true)) $table->uuid('client_uuid')->nullable()->index();
                if (in_array('location_accuracy', $missing, true)) $table->decimal('location_accuracy', 8, 2)->nullable();
                if (in_array('proxy_reason', $missing, true)) $table->text('proxy_reason')->nullable();
                if (in_array('proxy_actor_ip', $missing, true)) $table->string('proxy_actor_ip', 64)->nullable();
                if (in_array('proxy_device', $missing, true)) $table->string('proxy_device', 255)->nullable();
                if (in_array('offline_rejection_reason', $missing, true)) $table->text('offline_rejection_reason')->nullable();
            });
        }

        if (! Schema::hasTable('staff_attendance_sync_events')) {
            Schema::create('staff_attendance_sync_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('client_uuid');
                $table->unsignedBigInteger('staff_user_id')->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('attendance_record_id')->nullable()->index();
                $table->string('action', 20);
                $table->date('attendance_date');
                $table->dateTime('local_timestamp');
                $table->string('status', 20)->default('pending')->index();
                $table->text('rejection_reason')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'client_uuid'], 'staff_attendance_sync_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_sync_events');
        Schema::table('staff_attendance_records', function (Blueprint $table): void {
            foreach (['client_uuid','location_accuracy','proxy_reason','proxy_actor_ip','proxy_device','offline_rejection_reason'] as $column) {
                if (Schema::hasColumn('staff_attendance_records', $column)) $table->dropColumn($column);
            }
        });
    }
};
