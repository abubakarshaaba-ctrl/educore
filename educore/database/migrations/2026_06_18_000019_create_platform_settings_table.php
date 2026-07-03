<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('platform_name')->default('Enterprise SMS');
                $table->string('paystack_public_key')->nullable();
                $table->string('paystack_secret_key')->nullable();
                $table->string('flutterwave_public_key')->nullable();
                $table->string('flutterwave_secret_key')->nullable();
                $table->string('active_gateway')->default('paystack');
                $table->boolean('is_live')->default(false);
                $table->string('support_email')->nullable();
                $table->string('support_phone')->nullable();
                $table->integer('trial_days')->default(30);
                $table->timestamps();
            });
        }

        // Add payment_reference to platform_invoices if missing
        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('platform_invoices', 'payment_reference')) {
                    $table->string('payment_reference')->nullable()->after('status');
                }
                if (!Schema::hasColumn('platform_invoices', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('payment_reference');
                }
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
