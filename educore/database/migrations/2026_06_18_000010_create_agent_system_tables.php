<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── platform_agents ───────────────────────────────────────────
        if (!Schema::hasTable('platform_agents')) {
            Schema::create('platform_agents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('state')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(10);
                $table->decimal('total_earned', 12, 2)->default(0);
                $table->decimal('total_paid', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('referral_code', 20)->unique();
                // Banking for payouts
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number', 20)->nullable();
                $table->string('bank_account_name')->nullable();
                $table->text('notes')->nullable();
                // Portal login
                $table->string('password')->nullable();
                $table->rememberToken();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }

        // ── agent_referrals ───────────────────────────────────────────
        if (!Schema::hasTable('agent_referrals')) {
            Schema::create('agent_referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id');
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->decimal('sale_amount', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->enum('status', ['pending','approved','paid','rejected'])->default('pending');
                $table->date('sale_date')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->foreign('agent_id')->references('id')->on('platform_agents')->onDelete('cascade');
            });
        } else {
            // Add missing columns to existing table
            Schema::table('agent_referrals', function (Blueprint $table) {
                if (!Schema::hasColumn('agent_referrals', 'notes')) {
                    $table->string('notes')->nullable();
                }
            });
        }

        // ── agent_payouts ─────────────────────────────────────────────
        if (!Schema::hasTable('agent_payouts')) {
            Schema::create('agent_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id');
                $table->decimal('amount', 12, 2);
                $table->string('reference')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->enum('status', ['pending','paid'])->default('paid');
                $table->text('note')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();
                $table->foreign('agent_id')->references('id')->on('platform_agents')->onDelete('cascade');
            });
        }

        // ── agent_messages (super admin → agents broadcast) ───────────
        if (!Schema::hasTable('agent_messages')) {
            Schema::create('agent_messages', function (Blueprint $table) {
                $table->id();
                $table->string('subject');
                $table->text('body');
                $table->enum('audience', ['all','active','inactive'])->default('all');
                $table->unsignedBigInteger('sent_by');
                $table->timestamp('sent_at')->useCurrent();
                $table->timestamps();
            });
        }

        // ── agent_message_reads ───────────────────────────────────────
        if (!Schema::hasTable('agent_message_reads')) {
            Schema::create('agent_message_reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('agent_id');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->unique(['message_id','agent_id']);
            });
        }

        // Add agent columns to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'referred_by_agent_id')) {
                $table->unsignedBigInteger('referred_by_agent_id')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'referral_code_used')) {
                $table->string('referral_code_used', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_message_reads');
        Schema::dropIfExists('agent_messages');
        Schema::dropIfExists('agent_payouts');
        Schema::dropIfExists('agent_referrals');
        Schema::dropIfExists('platform_agents');
    }
};
