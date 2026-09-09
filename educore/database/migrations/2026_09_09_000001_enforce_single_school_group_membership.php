<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('school_group_members')) {
            return;
        }

        $duplicates = DB::table('school_group_members')
            ->select('tenant_id')
            ->groupBy('tenant_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('tenant_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce single school-group membership because duplicate tenant memberships exist: '
                .$duplicates->implode(', ')
                .'. Resolve these records before rerunning migrations.'
            );
        }

        Schema::table('school_group_members', function (Blueprint $table): void {
            $table->unique('tenant_id', 'school_group_members_tenant_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('school_group_members')) {
            return;
        }

        Schema::table('school_group_members', function (Blueprint $table): void {
            $table->dropUnique('school_group_members_tenant_unique');
        });
    }
};
