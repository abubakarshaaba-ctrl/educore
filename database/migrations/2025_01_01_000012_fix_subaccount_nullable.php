<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_bank_subaccounts', function (Blueprint $table) {
            // Make gateway code nullable - not all schools use payment gateways
            $table->string('gateway_subaccount_code')->nullable()->change();
            // Add missing columns
            if (!Schema::hasColumn('school_bank_subaccounts', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('school_bank_subaccounts', 'description')) {
                $table->text('description')->nullable();
            }
        });

        // Fix termly_summaries: add form_tutor_remark alias if teacher_remark was used
        if (Schema::hasTable('termly_summaries')) {
            if (!Schema::hasColumn('termly_summaries', 'teacher_remark') &&
                Schema::hasColumn('termly_summaries', 'form_tutor_remark')) {
                // Column exists with correct name already, nothing to do
            }
        }
    }

    public function down(): void
    {
        Schema::table('school_bank_subaccounts', function (Blueprint $table) {
            $table->string('gateway_subaccount_code')->nullable(false)->change();
        });
    }
};
