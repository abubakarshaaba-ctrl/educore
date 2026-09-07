<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'subscription_expires_at')) {
            return;
        }

        $counts = Schema::hasTable('students')
            ? DB::table('students')
                ->selectRaw('tenant_id, COUNT(*) as active_students')
                ->where('status', 'active')
                ->groupBy('tenant_id')
                ->pluck('active_students', 'tenant_id')
            : collect();

        DB::table('tenants')
            ->select(['id', 'subscription_expires_at'])
            ->orderBy('id')
            ->chunkById(200, function ($tenants) use ($counts): void {
                foreach ($tenants as $tenant) {
                    if ((int) ($counts[$tenant->id] ?? 0) <= 50 && $tenant->subscription_expires_at !== null) {
                        DB::table('tenants')->where('id', $tenant->id)->update([
                            'subscription_expires_at' => null,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Free accounts intentionally remain non-expiring.
    }
};
