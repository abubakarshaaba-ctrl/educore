<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds profile fields to users table:
 * - date_of_birth
 * - address
 * - passport_photo (path to uploaded image)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'passport_photo')) {
                $table->string('passport_photo', 255)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'date_of_birth'))   $table->dropColumn('date_of_birth');
            if (Schema::hasColumn('users', 'address'))         $table->dropColumn('address');
            if (Schema::hasColumn('users', 'passport_photo'))  $table->dropColumn('passport_photo');
        });
    }
};
