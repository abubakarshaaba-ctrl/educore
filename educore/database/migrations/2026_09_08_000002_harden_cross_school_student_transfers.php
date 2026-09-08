<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_transfers')) {
            return;
        }

        // The original table used an enum whose request state was "requested",
        // while the application has since standardized on "pending". Convert the
        // column to a normal bounded string so lifecycle states can evolve without
        // deployment-breaking enum/check-constraint drift.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE student_transfers MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            // Laravel's PostgreSQL enum grammar is a varchar + CHECK constraint.
            // Drop that old generated check before introducing the new state.
            DB::statement('ALTER TABLE student_transfers DROP CONSTRAINT IF EXISTS student_transfers_status_check');
            DB::statement("ALTER TABLE student_transfers ALTER COLUMN status TYPE VARCHAR(30) USING status::text");
            DB::statement("ALTER TABLE student_transfers ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement('ALTER TABLE student_transfers ALTER COLUMN status SET NOT NULL');
        } else {
            // SQLite (used by CI) also represents enum as a CHECK constraint.
            // Laravel rebuilds the table for this change, removing the obsolete
            // enum check while preserving the existing values and indexes.
            Schema::table('student_transfers', function (Blueprint $table): void {
                $table->string('status', 30)->default('pending')->change();
            });
        }

        DB::table('student_transfers')->where('status', 'requested')->update(['status' => 'pending']);
        DB::table('student_transfers')->where('status', 'approved')->update(['status' => 'completed']);

        $missing = [
            'destination_student_id' => !Schema::hasColumn('student_transfers', 'destination_student_id'),
            'approved_by' => !Schema::hasColumn('student_transfers', 'approved_by'),
            'completed_at' => !Schema::hasColumn('student_transfers', 'completed_at'),
            'rejected_by' => !Schema::hasColumn('student_transfers', 'rejected_by'),
            'rejected_at' => !Schema::hasColumn('student_transfers', 'rejected_at'),
        ];

        Schema::table('student_transfers', function (Blueprint $table) use ($missing): void {
            if ($missing['destination_student_id']) {
                $table->unsignedBigInteger('destination_student_id')->nullable()->after('student_id')->index();
            }
            if ($missing['approved_by']) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('requested_by')->index();
            }
            if ($missing['completed_at']) {
                $table->timestamp('completed_at')->nullable()->after('approved_at');
            }
            if ($missing['rejected_by']) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('completed_at')->index();
            }
            if ($missing['rejected_at']) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_transfers')) {
            return;
        }

        foreach ([
            'destination_student_id',
            'approved_by',
            'completed_at',
            'rejected_by',
            'rejected_at',
        ] as $column) {
            if (Schema::hasColumn('student_transfers', $column)) {
                Schema::table('student_transfers', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
