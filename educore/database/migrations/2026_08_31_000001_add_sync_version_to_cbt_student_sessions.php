<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cbt_student_sessions') && ! Schema::hasColumn('cbt_student_sessions', 'sync_version')) {
            Schema::table('cbt_student_sessions', function (Blueprint $table) {
                $table->unsignedInteger('sync_version')->default(0)->after('last_synced_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cbt_student_sessions') && Schema::hasColumn('cbt_student_sessions', 'sync_version')) {
            Schema::table('cbt_student_sessions', function (Blueprint $table) {
                $table->dropColumn('sync_version');
            });
        }
    }
};
