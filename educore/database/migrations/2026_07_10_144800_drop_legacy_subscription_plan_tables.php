<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full removal of the old tiered subscription model (subscription_plans,
 * tenant_subscriptions), superseded by the pay-per-student model in
 * App\Services\PricingService. platform_payments.subscription_id and
 * platform_invoices.plan_id are left in place as inert historical columns
 * (no FK, never written to going forward) rather than dropped, since they
 * still hold real payment-history data and neither is load-bearing for any
 * current code path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_payments') && Schema::hasColumn('platform_payments', 'subscription_id')) {
            Schema::table('platform_payments', function (Blueprint $table) {
                $table->dropForeign('fk_ppay_sub');
            });
        }

        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }

    public function down(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->integer('max_students')->default(500);
            $table->integer('max_staff')->default(50);
            $table->boolean('has_cbt')->default(false);
            $table->boolean('has_sms')->default(false);
            $table->boolean('has_paystack')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->foreign('tenant_id', 'fk_tsub_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id',   'fk_tsub_plan')->references('id')->on('subscription_plans')->nullOnDelete();

            $table->enum('status', ['active','expired','cancelled','trial'])->default('trial');
            $table->enum('billing_cycle', ['monthly','annual'])->default('annual');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->date('next_billing_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_tsub_status');
        });

        if (Schema::hasTable('platform_payments') && Schema::hasColumn('platform_payments', 'subscription_id')) {
            Schema::table('platform_payments', function (Blueprint $table) {
                $table->foreign('subscription_id', 'fk_ppay_sub')->references('id')->on('tenant_subscriptions')->nullOnDelete();
            });
        }
    }
};
