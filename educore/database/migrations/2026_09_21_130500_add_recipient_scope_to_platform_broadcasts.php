<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('platform_broadcasts')
            && ! Schema::hasColumn('platform_broadcasts', 'recipient_scope')
        ) {
            Schema::table('platform_broadcasts', function (Blueprint $table): void {
                $table->string('recipient_scope', 30)
                    ->default('tenant_admin')
                    ->after('target')
                    ->index();
            });
        }

        // Existing platform broadcasts were created before recipient selection
        // existed and must retain the current tenant-admin-only behavior.
        if (
            Schema::hasTable('announcements')
            && Schema::hasColumn('announcements', 'platform_broadcast_id')
            && Schema::hasColumn('announcements', 'audience')
        ) {
            DB::table('announcements')
                ->whereNotNull('platform_broadcast_id')
                ->update([
                    'audience' => 'tenant_admin',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('announcements')
            && Schema::hasColumn('announcements', 'platform_broadcast_id')
            && Schema::hasColumn('announcements', 'audience')
        ) {
            DB::table('announcements')
                ->whereNotNull('platform_broadcast_id')
                ->where('audience', 'tenant_admin')
                ->update([
                    'audience' => 'all',
                    'updated_at' => now(),
                ]);
        }

        if (
            Schema::hasTable('platform_broadcasts')
            && Schema::hasColumn('platform_broadcasts', 'recipient_scope')
        ) {
            Schema::table('platform_broadcasts', function (Blueprint $table): void {
                $table->dropIndex(['recipient_scope']);
                $table->dropColumn('recipient_scope');
            });
        }
    }
};
