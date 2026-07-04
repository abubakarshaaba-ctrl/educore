<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_attendance_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_attendance_settings', 'permanent_qr_secret')) {
                $table->string('permanent_qr_secret', 64)->nullable()->after('qr_secret_date');
            }
        });
    }
    public function down(): void
    {
        Schema::table('staff_attendance_settings', function (Blueprint $table) {
            if (Schema::hasColumn('staff_attendance_settings', 'permanent_qr_secret')) {
                $table->dropColumn('permanent_qr_secret');
            }
        });
    }
};
