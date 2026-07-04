<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds user_id to guardians table for parent portal login linkage.
 * Adds email to guardians if missing.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('tenant_id');
                $table->index('user_id', 'guardians_user_id_idx');
            }
            if (!Schema::hasColumn('guardians', 'email')) {
                $table->string('email', 180)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (Schema::hasColumn('guardians', 'user_id')) {
                $table->dropIndex('guardians_user_id_idx');
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('guardians', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
