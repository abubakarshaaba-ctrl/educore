<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a staff member fill in their own bank/account/TIN details from their
     * profile page once. After that first save, bank_details_locked is set true
     * and the profile page makes the fields read-only — only the accountant
     * (via Payroll → Salary Settings, unaffected by this lock) can change them.
     */
    public function up(): void
    {
        if (Schema::hasTable('staff_salary_settings')) {
            Schema::table('staff_salary_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_salary_settings', 'tax_identification_number')) {
                    $table->string('tax_identification_number')->nullable()->after('account_name');
                }
                if (!Schema::hasColumn('staff_salary_settings', 'bank_details_locked')) {
                    $table->boolean('bank_details_locked')->default(false)->after('tax_identification_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_salary_settings')) {
            Schema::table('staff_salary_settings', function (Blueprint $table) {
                if (Schema::hasColumn('staff_salary_settings', 'tax_identification_number')) {
                    $table->dropColumn('tax_identification_number');
                }
                if (Schema::hasColumn('staff_salary_settings', 'bank_details_locked')) {
                    $table->dropColumn('bank_details_locked');
                }
            });
        }
    }
};
