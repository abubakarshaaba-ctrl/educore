<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('termly_summaries', function (Blueprint $table) {
            // Class stats for report card summary
            if (!Schema::hasColumn('termly_summaries', 'class_highest_avg')) {
                $table->decimal('class_highest_avg', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('termly_summaries', 'class_lowest_avg')) {
                $table->decimal('class_lowest_avg', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('termly_summaries', 'grand_total')) {
                $table->decimal('grand_total', 8, 2)->default(0);
            }
        });

        // Add logo to tenants if not exists
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'logo_path')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('logo_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('termly_summaries', function (Blueprint $table) {
            $table->dropColumnIfExists('class_highest_avg');
            $table->dropColumnIfExists('class_lowest_avg');
            $table->dropColumnIfExists('grand_total');
        });
    }
};
