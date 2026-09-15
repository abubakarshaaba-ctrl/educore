<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timetable_configs') && ! Schema::hasColumn('timetable_configs', 'day_end_times')) {
            Schema::table('timetable_configs', function (Blueprint $table) {
                $table->json('day_end_times')->nullable()->after('school_end');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timetable_configs') && Schema::hasColumn('timetable_configs', 'day_end_times')) {
            Schema::table('timetable_configs', function (Blueprint $table) {
                $table->dropColumn('day_end_times');
            });
        }
    }
};
