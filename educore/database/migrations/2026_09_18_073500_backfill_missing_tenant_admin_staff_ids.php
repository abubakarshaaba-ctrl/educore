<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'staff_id')) {
            return;
        }

        $used = [];
        $highest = 1000;

        $register = function (?string $staffId) use (&$used, &$highest): void {
            $normalized = strtoupper(trim((string) $staffId));
            if ($normalized === '') {
                return;
            }

            $used[$normalized] = true;

            if (preg_match('/^STF(\d+)$/', $normalized, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        };

        DB::table('users')
            ->whereNotNull('staff_id')
            ->pluck('staff_id')
            ->each($register);

        if (
            Schema::hasTable('staff_profile_submissions')
            && Schema::hasColumn('staff_profile_submissions', 'staff_id')
        ) {
            DB::table('staff_profile_submissions')
                ->whereNotNull('staff_id')
                ->pluck('staff_id')
                ->each($register);
        }

        $query = DB::table('users')
            ->whereNotNull('tenant_id')
            ->where('is_super_admin', false)
            ->where('role', 'admin')
            ->where(function ($q): void {
                $q->whereNull('staff_id')->orWhere('staff_id', '');
            });

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        $query->orderBy('id')->chunkById(100, function ($admins) use (&$used, &$highest): void {
            foreach ($admins as $admin) {
                do {
                    $highest++;
                    $candidate = 'STF'.str_pad((string) $highest, 4, '0', STR_PAD_LEFT);
                } while (isset($used[$candidate]));

                DB::table('users')
                    ->where('id', $admin->id)
                    ->where(function ($q): void {
                        $q->whereNull('staff_id')->orWhere('staff_id', '');
                    })
                    ->update([
                        'staff_id' => $candidate,
                        'updated_at' => now(),
                    ]);

                $used[$candidate] = true;
            }
        });
    }

    public function down(): void
    {
        // Staff identifiers become operational identity data once issued.
        // Rollback must not erase IDs assigned to tenant administrators.
    }
};
