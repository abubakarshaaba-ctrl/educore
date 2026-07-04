<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-staff deductions — e.g. a school loan, cooperative contribution, or
     * child school-fees deduction — distinct from the flat tax/pension applied
     * to everyone. Links a staff member to a PayrollDeductionTemplate, with an
     * optional custom_amount that overrides the template's computed value
     * (so two staff can share a "Cooperative" template but owe different sums).
     */
    public function up(): void
    {
        if (Schema::hasTable('staff_deductions')) {
            return;
        }

        Schema::create('staff_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('payroll_deduction_template_id');
            $table->decimal('custom_amount', 10, 2)->nullable(); // null = use template's own value/percentage
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['staff_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_deductions');
    }
};
