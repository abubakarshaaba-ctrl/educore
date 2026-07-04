<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Payroll Deduction / Tax Templates ─────────────────────────
        if (!Schema::hasTable('payroll_deduction_templates')) {
            Schema::create('payroll_deduction_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');                     // e.g. "PAYE Tax", "Pension", "NHF"
                $table->enum('type', ['tax','pension','loan','other'])->default('other');
                $table->enum('calc_method', ['percentage','fixed'])->default('percentage');
                $table->decimal('value', 8, 2);             // % or ₦
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index('tenant_id');
            });
        }

        // ── Role-based Payroll Templates ──────────────────────────────
        if (!Schema::hasTable('payroll_role_templates')) {
            Schema::create('payroll_role_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('role');                     // matches User->role
                $table->string('label')->nullable();        // "Class Teacher", "Principal"
                $table->decimal('basic_salary', 10, 2)->default(0);
                $table->decimal('housing_allowance', 10, 2)->default(0);
                $table->decimal('transport_allowance', 10, 2)->default(0);
                $table->decimal('other_allowances', 10, 2)->default(0);
                $table->json('deduction_ids')->nullable();  // array of deduction template IDs
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id','role']);
            });
        }

        // ── Add template columns to payroll_items ─────────────────────
        Schema::table('payroll_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_items', 'basic_salary'))
                $table->decimal('basic_salary', 10, 2)->default(0);
            if (!Schema::hasColumn('payroll_items', 'housing_allowance'))
                $table->decimal('housing_allowance', 10, 2)->default(0);
            if (!Schema::hasColumn('payroll_items', 'transport_allowance'))
                $table->decimal('transport_allowance', 10, 2)->default(0);
            if (!Schema::hasColumn('payroll_items', 'tax_deduction'))
                $table->decimal('tax_deduction', 10, 2)->default(0);
            if (!Schema::hasColumn('payroll_items', 'pension_deduction'))
                $table->decimal('pension_deduction', 10, 2)->default(0);
            if (!Schema::hasColumn('payroll_items', 'other_deductions'))
                $table->decimal('other_deductions', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_role_templates');
        Schema::dropIfExists('payroll_deduction_templates');
    }
};
