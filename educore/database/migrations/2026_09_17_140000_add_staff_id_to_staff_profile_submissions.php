<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('staff_profile_submissions', 'staff_id')) {
            Schema::table('staff_profile_submissions', function (Blueprint $table) {
                $table->string('staff_id', 40)->nullable()->after('tenant_id');
                $table->unique('staff_id', 'staff_profile_submissions_staff_id_unique');
            });
        }

        $highest = 1000;
        $used = [];

        foreach (DB::table('users')->whereNotNull('staff_id')->pluck('staff_id') as $staffId) {
            $normalized = strtoupper(trim((string) $staffId));
            $used[$normalized] = true;

            if (preg_match('/^STF(\d+)$/', $normalized, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        // Preserve the ID already assigned to staff whose self-onboarding
        // submission was approved before this column existed.
        DB::table('staff_profile_submissions')
            ->where('status', 'approved')
            ->whereNull('staff_id')
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'email'])
            ->each(function ($submission) use (&$used, &$highest) {
                $staffId = DB::table('users')
                    ->where('tenant_id', $submission->tenant_id)
                    ->where('email', $submission->email)
                    ->whereNotNull('staff_id')
                    ->value('staff_id');

                if (!$staffId) {
                    return;
                }

                DB::table('staff_profile_submissions')
                    ->where('id', $submission->id)
                    ->update(['staff_id' => $staffId]);

                $normalized = strtoupper(trim((string) $staffId));
                $used[$normalized] = true;

                if (preg_match('/^STF(\d+)$/', $normalized, $matches)) {
                    $highest = max($highest, (int) $matches[1]);
                }
            });

        foreach (DB::table('staff_profile_submissions')->whereNotNull('staff_id')->pluck('staff_id') as $staffId) {
            $normalized = strtoupper(trim((string) $staffId));
            $used[$normalized] = true;

            if (preg_match('/^STF(\d+)$/', $normalized, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        // Reserve an ID for every currently pending short-link submission.
        DB::table('staff_profile_submissions')
            ->where('status', 'pending')
            ->whereNull('staff_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($submission) use (&$used, &$highest) {
                do {
                    $highest++;
                    $candidate = 'STF' . str_pad((string) $highest, 4, '0', STR_PAD_LEFT);
                } while (isset($used[$candidate]));

                DB::table('staff_profile_submissions')
                    ->where('id', $submission->id)
                    ->update(['staff_id' => $candidate]);

                $used[$candidate] = true;
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_profile_submissions', 'staff_id')) {
            Schema::table('staff_profile_submissions', function (Blueprint $table) {
                $table->dropUnique('staff_profile_submissions_staff_id_unique');
                $table->dropColumn('staff_id');
            });
        }
    }
};
