<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 1. Adds staff_id column to users (for ID-based staff login)
 * 2. Adds student_id column to users (for ID-based student login)
 * 3. Adds assistant_id to transport_routes (bus assistant)
 * 4. Converts users.role from ENUM to VARCHAR before migrating old teacher role
 * 5. Migrates old 'teacher' role → 'form_subject_teacher'
 */
return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Fix users.role first
        |--------------------------------------------------------------------------
        | The old users.role column is an ENUM in the current database. New roles
        | like principal, admission_officer, transport_officer, and
        | form_subject_teacher will fail unless the column is converted to VARCHAR.
        */
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(100) NULL");
        }

        // ── Add staff_id and student_id to users ─────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id', 40)->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id', 40)->nullable()->unique()->after('staff_id');
            }
        });

        // ── Add bus assistant to transport_routes ─────────────────────
        if (Schema::hasTable('transport_routes')) {
            Schema::table('transport_routes', function (Blueprint $table) {
                if (!Schema::hasColumn('transport_routes', 'assistant_id')) {
                    $table->unsignedBigInteger('assistant_id')->nullable()->after('driver_id');
                }
            });
        }

        // ── Migrate old 'teacher' role to 'form_subject_teacher' ─────
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'teacher')
                ->update(['role' => 'form_subject_teacher']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'form_subject_teacher')
                ->update(['role' => 'teacher']);
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'staff_id')) {
                    $table->dropColumn('staff_id');
                }

                if (Schema::hasColumn('users', 'student_id')) {
                    $table->dropColumn('student_id');
                }
            });
        }

        if (Schema::hasTable('transport_routes') && Schema::hasColumn('transport_routes', 'assistant_id')) {
            Schema::table('transport_routes', function (Blueprint $table) {
                $table->dropColumn('assistant_id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Do not convert users.role back to ENUM
        |--------------------------------------------------------------------------
        | Reverting to ENUM may truncate valid live roles like principal,
        | admission_officer, transport_officer, accountant, etc.
        */
    }
};
