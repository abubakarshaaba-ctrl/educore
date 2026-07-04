<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'qr_secret')) {
                $table->string('qr_secret', 64)->nullable()->after('attendance_pin');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'qr_secret')) $table->dropColumn('qr_secret');
        });
    }
};
