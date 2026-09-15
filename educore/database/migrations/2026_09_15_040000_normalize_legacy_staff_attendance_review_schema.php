<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalize staff-attendance columns used by offline review.
 *
 * The original production attendance tables pre-date the reconstructed
 * create_* migrations in this repository. Those create migrations correctly
 * skip tables that already exist, but that also means legacy MySQL/MariaDB
 * ENUMs and narrower column definitions can survive indefinitely and reject
 * newer values such as early, offline_review, offline_manual_review, approved
 * and rejected.
 *
 * Keep this migration deliberately limited to the columns used by the review
 * workflow. It is safe for freshly-created installations and repairs existing
 * production tables in place without deleting attendance data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureAttendanceColumns();
        $this->ensureOfflineColumns();

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('staff_attendance_records')) {
            // Legacy deployments may have ENUM status/method columns. Convert
            // them to bounded VARCHARs so the API's canonical values remain
            // forward-compatible.
            if (Schema::hasColumn('staff_attendance_records', 'status')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'present'");
            }
            if (Schema::hasColumn('staff_attendance_records', 'clock_in_method')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `clock_in_method` VARCHAR(30) NULL");
            }
            if (Schema::hasColumn('staff_attendance_records', 'clock_in_time')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `clock_in_time` TIME NULL");
            }
            if (Schema::hasColumn('staff_attendance_records', 'clock_in_lat')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `clock_in_lat` DECIMAL(10,7) NULL");
            }
            if (Schema::hasColumn('staff_attendance_records', 'clock_in_lng')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `clock_in_lng` DECIMAL(10,7) NULL");
            }
            if (Schema::hasColumn('staff_attendance_records', 'geo_verified')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `geo_verified` TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (Schema::hasColumn('staff_attendance_records', 'is_offline_upload')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `is_offline_upload` TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (Schema::hasColumn('staff_attendance_records', 'notes')) {
                DB::statement("ALTER TABLE `staff_attendance_records` MODIFY `notes` TEXT NULL");
            }
        }

        if (Schema::hasTable('staff_offline_clockins')) {
            if (Schema::hasColumn('staff_offline_clockins', 'status')) {
                DB::statement("ALTER TABLE `staff_offline_clockins` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
            }
            if (Schema::hasColumn('staff_offline_clockins', 'reject_reason')) {
                DB::statement("ALTER TABLE `staff_offline_clockins` MODIFY `reject_reason` TEXT NULL");
            }
        }
    }

    public function down(): void
    {
        // Do not attempt to recreate unknown legacy ENUM definitions. Reverting
        // this normalization could make already-stored canonical values invalid.
    }

    private function ensureAttendanceColumns(): void
    {
        if (! Schema::hasTable('staff_attendance_records')) {
            return;
        }

        Schema::table('staff_attendance_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_attendance_records', 'clock_in_method')) {
                $table->string('clock_in_method', 30)->nullable();
            }
            if (! Schema::hasColumn('staff_attendance_records', 'clocked_in_by')) {
                $table->unsignedBigInteger('clocked_in_by')->nullable();
            }
            if (! Schema::hasColumn('staff_attendance_records', 'clock_in_lat')) {
                $table->decimal('clock_in_lat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('staff_attendance_records', 'clock_in_lng')) {
                $table->decimal('clock_in_lng', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('staff_attendance_records', 'geo_verified')) {
                $table->boolean('geo_verified')->default(false);
            }
            if (! Schema::hasColumn('staff_attendance_records', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('staff_attendance_records', 'is_offline_upload')) {
                $table->boolean('is_offline_upload')->default(false);
            }
        });
    }

    private function ensureOfflineColumns(): void
    {
        if (! Schema::hasTable('staff_offline_clockins')) {
            return;
        }

        Schema::table('staff_offline_clockins', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_offline_clockins', 'reject_reason')) {
                $table->text('reject_reason')->nullable();
            }
        });
    }
};
