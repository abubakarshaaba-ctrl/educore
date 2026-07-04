<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cbt_student_sessions')) {
            return;
        }

        // SQLite stores Laravel enum columns as TEXT with a check constraint and
        // does not support MySQL's ALTER TABLE ... MODIFY syntax. The expanded
        // values are only required for MySQL/MariaDB production deployments.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE cbt_student_sessions
            MODIFY status ENUM(
                'not_started',
                'in_progress',
                'submitted',
                'graded',
                'completed',
                'expired',
                'cancelled'
            ) NOT NULL DEFAULT 'in_progress'
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('cbt_student_sessions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE cbt_student_sessions
            MODIFY status ENUM(
                'not_started',
                'in_progress',
                'submitted',
                'completed',
                'expired',
                'cancelled'
            ) NOT NULL DEFAULT 'in_progress'
        ");
    }
};
