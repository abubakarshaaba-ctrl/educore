<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_attendance_records', 'clock_in_photo')) {
                $table->string('clock_in_photo')->nullable()->after('geo_verified');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_photo')) {
                $table->string('proxy_photo')->nullable()->after('clock_in_photo');
            }
        });
    }
    public function down(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table) {
            $table->dropColumnIfExists('clock_in_photo');
            $table->dropColumnIfExists('proxy_photo');
        });
    }
};
