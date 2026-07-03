<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Platform Agents / Resellers ─────────────────────────────
        Schema::create('platform_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // % of each sale
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('referral_code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('agent_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->decimal('sale_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->date('sale_date');
            $table->timestamps();
            $table->index('agent_id');
        });

        // ── Parent Portal ───────────────────────────────────────────
        Schema::create('parent_portal_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('guardian_id');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Fee Reminder Log ────────────────────────────────────────
        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('channel')->default('sms'); // sms, email
            $table->string('recipient');
            $table->text('message');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Score Imports ────────────────────────────────────────────
        Schema::create('score_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('filename');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_failed')->default(0);
            $table->text('errors')->nullable();
            $table->enum('status', ['processing', 'done', 'failed'])->default('processing');
            $table->unsignedBigInteger('imported_by');
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Tenant custom domain ─────────────────────────────────────
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
    }

    public function down(): void
    {
        Schema::dropIfExists('score_imports');
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('parent_portal_accounts');
        Schema::dropIfExists('agent_referrals');
        Schema::dropIfExists('platform_agents');
    }
};
