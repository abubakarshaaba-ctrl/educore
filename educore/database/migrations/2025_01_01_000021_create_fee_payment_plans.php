<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Fee Payment Plans (templates) ─────────────────────────
        if (!Schema::hasTable('fee_payment_plans')) {
            Schema::create('fee_payment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('installments_count');
                $table->json('installment_schedule');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->decimal('surcharge_pct', 5, 2)->default(0);
                $table->timestamps();
                $table->index('tenant_id');
            });
        }

        // ── Invoice Payment Plan Assignment ───────────────────────
        if (!Schema::hasTable('invoice_payment_plans')) {
            Schema::create('invoice_payment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('plan_id');
                $table->timestamps();
                $table->unique('invoice_id');
                $table->index('tenant_id');
            });
        }

        // ── Installment Records ───────────────────────────────────
        if (!Schema::hasTable('fee_installments')) {
            Schema::create('fee_installments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('invoice_payment_plan_id');
                $table->integer('installment_number');
                $table->decimal('amount_due', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
                $table->boolean('reminder_sent')->default(false);
                $table->timestamps();
                $table->index(['invoice_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'due_date']);
            });
        }

        // ── Add plan columns to invoices table ────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'has_payment_plan'))
                $table->boolean('has_payment_plan')->default(false);
            if (!Schema::hasColumn('invoices', 'next_installment_due'))
                $table->date('next_installment_due')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('invoice_payment_plans');
        Schema::dropIfExists('fee_payment_plans');
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'has_payment_plan'))
                $table->dropColumn('has_payment_plan');
            if (Schema::hasColumn('invoices', 'next_installment_due'))
                $table->dropColumn('next_installment_due');
        });
    }
};
