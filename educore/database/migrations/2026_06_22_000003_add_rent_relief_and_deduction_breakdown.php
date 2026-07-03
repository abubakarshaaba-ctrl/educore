<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional annual rent paid — used for the Nigeria Tax Act 2025 "rent relief"
        // (20% of annual rent, capped at ₦500,000) that replaced the old CRA.
        // Left at 0 by default, which simply means no relief is applied (safe default,
        // never under-taxes).
        if (Schema::hasTable('staff_salary_settings') && !Schema::hasColumn('staff_salary_settings', 'annual_rent_paid')) {
            Schema::table('staff_salary_settings', function (Blueprint $table) {
                $table->decimal('annual_rent_paid', 12, 2)->default(0)->after('other_allowances');
            });
        }

        // Snapshot of the per-staff deduction lines applied to a given payroll item
        // (e.g. "Cooperative Loan: ₦5,000"), so payslips stay accurate even if the
        // deduction template or amount changes later.
        if (Schema::hasTable('payroll_items') && !Schema::hasColumn('payroll_items', 'deduction_breakdown')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->json('deduction_breakdown')->nullable()->after('other_deductions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_salary_settings') && Schema::hasColumn('staff_salary_settings', 'annual_rent_paid')) {
            Schema::table('staff_salary_settings', function (Blueprint $table) {
                $table->dropColumn('annual_rent_paid');
            });
        }
        if (Schema::hasTable('payroll_items') && Schema::hasColumn('payroll_items', 'deduction_breakdown')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->dropColumn('deduction_breakdown');
            });
        }
    }
};
