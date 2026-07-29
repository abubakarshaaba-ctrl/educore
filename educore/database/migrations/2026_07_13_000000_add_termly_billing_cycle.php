<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->enum('billing_cycle', ['monthly', 'annual', 'termly'])->default('termly')->change();
                // Invoices no longer require a plan under the pay-per-student model.
                $table->unsignedBigInteger('plan_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('tenant_subscriptions')) {
            Schema::table('tenant_subscriptions', function (Blueprint $table) {
                $table->enum('billing_cycle', ['monthly', 'annual', 'termly'])->default('termly')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly')->change();
                $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('tenant_subscriptions')) {
            Schema::table('tenant_subscriptions', function (Blueprint $table) {
                $table->enum('billing_cycle', ['monthly', 'annual'])->default('annual')->change();
            });
        }
    }
};
