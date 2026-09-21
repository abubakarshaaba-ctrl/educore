<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('announcements')
            || ! Schema::hasColumn('announcements', 'platform_broadcast_id')
            || ! Schema::hasColumn('announcements', 'audience')
        ) {
            return;
        }

        DB::table('announcements')
            ->whereNotNull('platform_broadcast_id')
            ->update([
                'audience' => 'tenant_admin',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('announcements')
            || ! Schema::hasColumn('announcements', 'platform_broadcast_id')
            || ! Schema::hasColumn('announcements', 'audience')
        ) {
            return;
        }

        DB::table('announcements')
            ->whereNotNull('platform_broadcast_id')
            ->where('audience', 'tenant_admin')
            ->update([
                'audience' => 'all',
                'updated_at' => now(),
            ]);
    }
};
