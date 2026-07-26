<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'students_capacity')) {
            Schema::table('tenants', function (Blueprint $table) {
                // Null = still on the free tier (up to PricingService::FREE_THRESHOLD).
                // Set to the paid-for student count once an invoice is paid.
                $table->unsignedInteger('students_capacity')->nullable()->after('subscription_expires_at');
            });
        }

        if (Schema::hasTable('platform_invoices') && !Schema::hasColumn('platform_invoices', 'student_count')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->unsignedInteger('student_count')->nullable()->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'students_capacity')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('students_capacity');
            });
        }

        if (Schema::hasTable('platform_invoices') && Schema::hasColumn('platform_invoices', 'student_count')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->dropColumn('student_count');
            });
        }
    }
};
