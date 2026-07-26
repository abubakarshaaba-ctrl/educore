<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            DB::statement("ALTER TABLE platform_invoices MODIFY billing_cycle ENUM('monthly','annual','termly') DEFAULT 'termly'");
            // Invoices no longer require a plan under the pay-per-student model.
            DB::statement("ALTER TABLE platform_invoices MODIFY plan_id BIGINT UNSIGNED NULL");
        }

        if (Schema::hasTable('tenant_subscriptions')) {
            DB::statement("ALTER TABLE tenant_subscriptions MODIFY billing_cycle ENUM('monthly','annual','termly') DEFAULT 'termly'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            DB::statement("ALTER TABLE platform_invoices MODIFY billing_cycle ENUM('monthly','annual') DEFAULT 'monthly'");
            DB::statement("ALTER TABLE platform_invoices MODIFY plan_id BIGINT UNSIGNED NOT NULL");
        }

        if (Schema::hasTable('tenant_subscriptions')) {
            DB::statement("ALTER TABLE tenant_subscriptions MODIFY billing_cycle ENUM('monthly','annual') DEFAULT 'annual'");
        }
    }
};
