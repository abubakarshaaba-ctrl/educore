<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->text('lan_sync_token')->nullable()->after('status');
            $table->timestamp('lan_exported_at')->nullable()->after('lan_sync_token');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->dropColumn(['lan_sync_token', 'lan_exported_at']);
        });
    }
};
