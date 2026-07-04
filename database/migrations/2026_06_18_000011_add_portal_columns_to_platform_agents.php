<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform_agents table already exists (created before migration 000010).
 * This migration safely adds the portal login columns that migration 000010
 * assumed would be added via Schema::create but couldn't because the table existed.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('platform_agents')) return;

        Schema::table('platform_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('platform_agents', 'password')) {
                $table->string('password')->nullable()->after('referral_code');
            }
            if (!Schema::hasColumn('platform_agents', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (!Schema::hasColumn('platform_agents', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('platform_agents', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('platform_agents', 'bank_account_number')) {
                $table->string('bank_account_number', 20)->nullable();
            }
            if (!Schema::hasColumn('platform_agents', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable();
            }
            if (!Schema::hasColumn('platform_agents', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // Also create agent_referrals if missing (may have existed before 000010)
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
            Schema::table('agent_referrals', function (Blueprint $table) {
                if (!Schema::hasColumn('agent_referrals', 'notes')) {
                    $table->string('notes')->nullable();
                }
            });
        }

        // agent_payouts
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

        // agent_messages
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

        // agent_message_reads
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

        // Add referral tracking columns to tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'referred_by_agent_id')) {
                $table->unsignedBigInteger('referred_by_agent_id')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'referral_code_used')) {
                $table->string('referral_code_used', 20)->nullable();
            }
        });
    }

    public function down(): void {}
};
