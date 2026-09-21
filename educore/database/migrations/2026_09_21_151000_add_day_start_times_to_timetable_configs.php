<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('timetable_configs')
            && ! Schema::hasColumn('timetable_configs', 'day_start_times')
        ) {
            Schema::table('timetable_configs', function (Blueprint $table): void {
                $table->json('day_start_times')->nullable()->after('school_start');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('timetable_configs')
            && Schema::hasColumn('timetable_configs', 'day_start_times')
        ) {
            Schema::table('timetable_configs', function (Blueprint $table): void {
                $table->dropColumn('day_start_times');
            });
        }
    }
};
