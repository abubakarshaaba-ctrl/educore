<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateway_configs')) {
            return;
        }

        // Widen the gateway enum to include monnify
        DB::statement("ALTER TABLE `payment_gateway_configs`
            MODIFY COLUMN `gateway` ENUM('paystack', 'flutterwave', 'monnify') NOT NULL DEFAULT 'paystack'");

        // Add contract_code needed by Monnify (Paystack/Flutterwave don't use it)
        if (!Schema::hasColumn('payment_gateway_configs', 'contract_code')) {
            Schema::table('payment_gateway_configs', function (Blueprint $table) {
                $table->string('contract_code')->nullable()->after('secret_key');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_gateway_configs')) {
            return;
        }
        DB::statement("ALTER TABLE `payment_gateway_configs`
            MODIFY COLUMN `gateway` ENUM('paystack', 'flutterwave') NOT NULL DEFAULT 'paystack'");

        if (Schema::hasColumn('payment_gateway_configs', 'contract_code')) {
            Schema::table('payment_gateway_configs', function (Blueprint $table) {
                $table->dropColumn('contract_code');
            });
        }
    }
};
