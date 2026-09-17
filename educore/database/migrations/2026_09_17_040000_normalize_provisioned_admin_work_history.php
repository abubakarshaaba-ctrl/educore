<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_work_histories')) {
            return;
        }

        foreach (['employment_type', 'appointment_type', 'reason'] as $column) {
            if (!Schema::hasColumn('staff_work_histories', $column)) {
                return;
            }
        }

        DB::table('staff_work_histories')
            ->where('appointment_type', 'initial_admin')
            ->where('reason', 'Initial tenant administrator provisioned.')
            ->where('employment_type', 'full_time')
            ->update(['employment_type' => null]);
    }

    public function down(): void
    {
        // This migration intentionally does not recreate fabricated employment
        // data. A null employment type means the value was never established.
    }
};
