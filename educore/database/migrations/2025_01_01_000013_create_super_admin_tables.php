<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Subscription Plans ──────────────────────────────────────
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Basic, Standard, Premium
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

        // ── Tenant Subscriptions ────────────────────────────────────
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
            $table->string('payment_method')->nullable();   // bank_transfer, card, cash
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_tsub_status');
        });

        // ── Platform Payments (money received from schools) ──────────
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->foreign('tenant_id',       'fk_ppay_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subscription_id', 'fk_ppay_sub')->references('id')->on('tenant_subscriptions')->nullOnDelete();

            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending','confirmed','failed','refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_channel')->nullable();  // bank name, card type
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // ── Platform Settings ────────────────────────────────────────
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');  // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // ── Seed default plans ────────────────────────────────────────
        DB::table('subscription_plans')->insert([
            ['name'=>'Basic','slug'=>'basic','description'=>'Up to 300 students, core modules','monthly_price'=>15000,'annual_price'=>150000,'max_students'=>300,'max_staff'=>20,'has_cbt'=>0,'has_sms'=>0,'has_paystack'=>0,'features'=>json_encode(['Students','Classes','Scores','Report Cards','Attendance']),'is_active'=>1,'sort_order'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Standard','slug'=>'standard','description'=>'Up to 800 students, all core + CBT','monthly_price'=>25000,'annual_price'=>250000,'max_students'=>800,'max_staff'=>50,'has_cbt'=>1,'has_sms'=>0,'has_paystack'=>0,'features'=>json_encode(['Everything in Basic','CBT Exams','Timetable','Skills Rating']),'is_active'=>1,'sort_order'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Premium','slug'=>'premium','description'=>'Unlimited students, all features','monthly_price'=>45000,'annual_price'=>450000,'max_students'=>9999,'max_staff'=>999,'has_cbt'=>1,'has_sms'=>1,'has_paystack'=>1,'features'=>json_encode(['Everything in Standard','SMS Notifications','Payment Integration','Priority Support']),'is_active'=>1,'sort_order'=>3,'created_at'=>now(),'updated_at'=>now()],
        ]);

        // Default platform settings
        DB::table('platform_settings')->insert([
            ['key'=>'platform_name','value'=>'Enterprise SMS','type'=>'string','group'=>'general','label'=>'Platform Name','created_at'=>now(),'updated_at'=>now()],
            ['key'=>'support_email','value'=>'support@enterprisesms.ng','type'=>'string','group'=>'general','label'=>'Support Email','created_at'=>now(),'updated_at'=>now()],
            ['key'=>'trial_days','value'=>'30','type'=>'integer','group'=>'billing','label'=>'Free Trial Days','created_at'=>now(),'updated_at'=>now()],
            ['key'=>'grace_period_days','value'=>'7','type'=>'integer','group'=>'billing','label'=>'Grace Period (days after expiry)','created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payments');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('platform_settings');
    }
};
