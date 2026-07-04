<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── School Expenses ─────────────────────────────────────────
        Schema::create('school_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('title');
            $table->string('category'); // utilities, supplies, maintenance, salary, transport, etc
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Staff Payroll ───────────────────────────────────────────
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title'); // e.g. "January 2026 Salary"
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('staff_id');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('pension_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index(['payroll_period_id', 'staff_id']);
        });

        // ── Staff Salary Setup ──────────────────────────────────────
        Schema::create('staff_salary_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id')->unique();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('staff_salary_settings');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('school_expenses');
    }
};
