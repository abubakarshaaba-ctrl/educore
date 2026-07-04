<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Parent-Teacher Messages ─────────────────────────────────
        // Already have parent_messages table — add thread/reply support
        if (!Schema::hasTable('message_threads')) {
            Schema::create('message_threads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->string('subject');
                $table->unsignedBigInteger('initiated_by');   // user_id
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->timestamps();
                $table->index('tenant_id');
                $table->index('student_id');
            });
        }

        if (!Schema::hasTable('message_thread_replies')) {
            Schema::create('message_thread_replies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('thread_id');
                $table->unsignedBigInteger('sender_id');
                $table->text('body');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['thread_id']);
            });
        }

        // ── Payment Gateway Config (per tenant) ─────────────────────
        if (!Schema::hasTable('payment_gateway_configs')) {
            Schema::create('payment_gateway_configs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->enum('gateway', ['paystack', 'flutterwave'])->default('paystack');
                $table->string('public_key')->nullable();
                $table->string('secret_key')->nullable();    // encrypted at rest
                $table->boolean('is_live')->default(false);  // false = test mode
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        // ── Online Payment Transactions (gateway-initiated) ──────────
        if (!Schema::hasTable('online_payment_logs')) {
            Schema::create('online_payment_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('student_id');
                $table->string('gateway');                    // paystack | flutterwave
                $table->string('reference')->unique();        // gateway reference
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pending','success','failed'])->default('pending');
                $table->json('gateway_response')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index('tenant_id');
                $table->index('reference');
            });
        }

        // ── SMS/Email Notification Queue ─────────────────────────────
        if (!Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('channel');        // sms | email | push
                $table->string('recipient');       // phone or email
                $table->string('subject')->nullable();
                $table->text('body');
                $table->string('gateway')->default('termii');  // termii | africas_talking | smtp
                $table->enum('status', ['pending','sent','failed'])->default('pending');
                $table->integer('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        // ── Student Transfers ─────────────────────────────────────────
        if (!Schema::hasTable('student_transfers')) {
            Schema::create('student_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('from_tenant_id');
                $table->unsignedBigInteger('to_tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->string('student_name');
                $table->string('admission_number');
                $table->enum('status', ['requested','approved','rejected','completed'])->default('requested');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('requested_by');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->index('from_tenant_id');
                $table->index('to_tenant_id');
            });
        }

        // ── Push Notification Subscriptions ──────────────────────────
        if (!Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('tenant_id');
                $table->string('endpoint')->unique();
                $table->string('p256dh_key');
                $table->string('auth_key');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('user_id');
            });
        }

        // ── White-label domain settings ───────────────────────────────
        // Each column added in a separate Schema::table call so 'after' works safely
        if (!Schema::hasColumn('tenants', 'custom_domain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('custom_domain')->nullable();
            });
        }
        if (!Schema::hasColumn('tenants', 'agent_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->unsignedBigInteger('agent_id')->nullable();
            });
        }
        if (!Schema::hasColumn('tenants', 'domain_verified')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('domain_verified')->default(false);
            });
        }
        if (!Schema::hasColumn('tenants', 'primary_color')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('primary_color', 10)->default('#2563EB');
            });
        }
        if (!Schema::hasColumn('tenants', 'secondary_color')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('secondary_color', 10)->default('#1E40AF');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('student_transfers');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('online_payment_logs');
        Schema::dropIfExists('payment_gateway_configs');
        Schema::dropIfExists('message_thread_replies');
        Schema::dropIfExists('message_threads');
    }
};
