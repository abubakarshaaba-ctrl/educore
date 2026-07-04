<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable PAYE tax bands, per tenant. Nigeria's PAYE structure changed
     * under the Nigeria Tax Act 2025 (effective 1 Jan 2026). We deliberately do
     * NOT hardcode a single fixed formula — bands are stored here so a school
     * can correct/confirm the exact thresholds with their accountant or the
     * official FIRS/NRS schedule without needing a code change. If a tenant has
     * no rows here, the payroll engine falls back to an in-code default.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_tax_bands')) {
            return;
        }

        Schema::create('payroll_tax_bands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('lower_bound', 12, 2);          // annual taxable income this band starts at
            $table->decimal('upper_bound', 12, 2)->nullable(); // null = no ceiling (top band)
            $table->decimal('rate_percent', 5, 2);           // e.g. 15.00
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_bands');
    }
};
