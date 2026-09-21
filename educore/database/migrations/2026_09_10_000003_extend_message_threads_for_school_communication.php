<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_threads')) {
            return;
        }

        // Older installations created student_id as NOT NULL. Internal staff,
        // parent and broadcast conversations deliberately have no student, so
        // make this repair explicit and independent of doctrine/dbal.
        if (Schema::hasColumn('message_threads', 'student_id')) {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE message_threads MODIFY student_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE message_threads ALTER COLUMN student_id DROP NOT NULL');
            } elseif ($driver !== 'sqlite') {
                Schema::table('message_threads', function (Blueprint $table): void {
                    $table->unsignedBigInteger('student_id')->nullable()->change();
                });
            }
        }

        if (! Schema::hasColumn('message_threads', 'conversation_type')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->string('conversation_type', 40)->default('student')->after('student_id');
            });
        }

        if (! Schema::hasColumn('message_threads', 'recipient_user_id')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('conversation_type');
            });
        }

        if (! Schema::hasColumn('message_threads', 'audience')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->string('audience', 40)->nullable()->after('recipient_user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('message_threads')) {
            return;
        }

        foreach (['audience', 'recipient_user_id', 'conversation_type'] as $column) {
            if (Schema::hasColumn('message_threads', $column)) {
                Schema::table('message_threads', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
