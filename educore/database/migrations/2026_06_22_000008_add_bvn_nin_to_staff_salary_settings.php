<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_salary_settings')) {
            return;
        }
        Schema::table('staff_salary_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_salary_settings', 'bvn')) {
                $table->string('bvn', 11)->nullable()->after('tax_identification_number');
            }
            if (!Schema::hasColumn('staff_salary_settings', 'nin')) {
                $table->string('nin', 11)->nullable()->after('bvn');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('staff_salary_settings')) return;
        Schema::table('staff_salary_settings', function (Blueprint $table) {
            if (Schema::hasColumn('staff_salary_settings', 'bvn')) $table->dropColumn('bvn');
            if (Schema::hasColumn('staff_salary_settings', 'nin')) $table->dropColumn('nin');
        });
    }
};
