<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) return;

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'features')) {
                $table->json('features')->nullable()->after('has_paystack');
            }
            if (!Schema::hasColumn('subscription_plans', 'max_staff')) {
                $table->integer('max_staff')->default(50)->after('max_students');
            }
            if (!Schema::hasColumn('subscription_plans', 'has_cbt')) {
                $table->boolean('has_cbt')->default(false)->after('max_staff');
            }
            if (!Schema::hasColumn('subscription_plans', 'has_sms')) {
                $table->boolean('has_sms')->default(false)->after('has_cbt');
            }
        });
    }

    public function down(): void {}
};
