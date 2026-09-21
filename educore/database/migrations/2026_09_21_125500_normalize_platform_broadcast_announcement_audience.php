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

        // Keep the established announcement audience vocabulary unchanged.
        // Platform recipient restriction is enforced by platform_broadcast_id,
        // not by introducing a new audience value.
        DB::table('announcements')
            ->whereNotNull('platform_broadcast_id')
            ->where('audience', 'tenant_admin')
            ->update([
                'audience' => 'all',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally no-op. Restoring the temporary tenant_admin audience
        // would reintroduce the compatibility issue this migration corrects.
    }
};
