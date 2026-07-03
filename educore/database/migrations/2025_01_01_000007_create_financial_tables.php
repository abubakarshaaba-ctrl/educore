<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_bank_subaccounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'fk_banksubaccts_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('purpose_name');
            $table->string('gateway_subaccount_code');
            $table->string('bank_name');
            $table->string('account_number', 10);
            $table->string('account_name');
            $table->enum('gateway', ['paystack','monnify','flutterwave'])->default('paystack');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active'], 'idx_banksubaccts_active');
        });

        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('school_bank_subaccount_id');
            $table->foreign('tenant_id', 'fk_feecats_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('school_bank_subaccount_id', 'fk_feecats_subaccount')->references('id')->on('school_bank_subaccounts')->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
            $table->index(['tenant_id'], 'idx_feecats_tenant');
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('term_id');
            $table->foreign('tenant_id', 'fk_feestructs_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('fee_category_id', 'fk_feestructs_feecat')->references('id')->on('fee_categories')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_feestructs_classlevel')->references('id')->on('class_levels')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_feestructs_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id','fee_category_id','class_level_id','term_id'], 'uq_feestruct');
            $table->index(['tenant_id','class_level_id','term_id'], 'idx_feestructs_level_term');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->foreign('tenant_id', 'fk_invoices_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_invoices_student')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('term_id', 'fk_invoices_term')->references('id')->on('terms')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_invoices_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->enum('status', ['unpaid','partially_paid','paid','waived','overpaid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id','student_id','term_id','session_id'], 'uq_invoice');
            $table->index(['tenant_id', 'status'], 'idx_invoices_tenant_status');
            $table->index(['tenant_id', 'student_id'], 'idx_invoices_tenant_student');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->foreign('tenant_id', 'fk_invitems_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invoice_id', 'fk_invitems_invoice')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('fee_category_id', 'fk_invitems_feecat')->references('id')->on('fee_categories')->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->index(['tenant_id', 'invoice_id'], 'idx_invitems_invoice');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->foreign('tenant_id', 'fk_payments_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invoice_id', 'fk_payments_invoice')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('student_id', 'fk_payments_student')->references('id')->on('students')->cascadeOnDelete();
            $table->string('gateway_reference')->unique();
            $table->string('gateway')->default('paystack');
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending','success','failed','reversed'])->default('pending');
            $table->json('gateway_response')->nullable();
            $table->json('split_breakdown')->nullable();
            $table->string('paid_by_name')->nullable();
            $table->string('paid_by_phone')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'invoice_id'], 'idx_payments_invoice');
            $table->index(['tenant_id', 'status'], 'idx_payments_status');
            $table->index(['tenant_id', 'paid_at'], 'idx_payments_paid_at');
        });

        Schema::create('invoice_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('tenant_id', 'fk_invdisc_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invoice_id', 'fk_invdisc_invoice')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('approved_by', 'fk_invdisc_approvedby')->references('id')->on('users')->nullOnDelete();
            $table->string('reason');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_discounts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
        Schema::dropIfExists('school_bank_subaccounts');
    }
};
