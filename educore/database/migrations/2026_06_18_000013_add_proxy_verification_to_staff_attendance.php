<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add attendance PIN to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'attendance_pin')) {
                $table->string('attendance_pin', 255)->nullable()->after('staff_id');
            }
        });

        // Add proxy verification columns to staff_attendance_records
        if (Schema::hasTable('staff_attendance_records')) {
            Schema::table('staff_attendance_records', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_attendance_records', 'proxy_verified')) {
                    $table->boolean('proxy_verified')->default(false)->after('geo_verified');
                }
                if (!Schema::hasColumn('staff_attendance_records', 'proxy_pin_used')) {
                    $table->boolean('proxy_pin_used')->default(false)->after('proxy_verified');
                }
            });
        }

        // Pending proxy requests (colleague initiates → staff approves via PIN or OTP)
        if (!Schema::hasTable('staff_proxy_requests')) {
            Schema::create('staff_proxy_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('target_user_id');    // the staff being clocked in
                $table->unsignedBigInteger('requested_by');      // colleague doing the proxy
                $table->date('attendance_date');
                $table->time('clock_in_time');
                $table->string('qr_token')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                // Verification
                $table->enum('verification_method', ['pin','otp'])->default('pin');
                $table->string('otp_code', 6)->nullable();       // system-generated OTP sent via SMS
                $table->timestamp('otp_expires_at')->nullable();
                $table->integer('pin_attempts')->default(0);
                $table->enum('status', ['pending','approved','rejected','expired'])->default('pending');
                $table->string('reject_reason')->nullable();
                $table->timestamps();
                // Auto-generated name for this column set exceeds MySQL's
                // 64-char identifier limit — give it an explicit short one.
                $table->index(['tenant_id','target_user_id','attendance_date'], 'idx_staff_proxy_requests_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'attendance_pin')) $table->dropColumn('attendance_pin');
        });
        Schema::dropIfExists('staff_proxy_requests');
    }
};
