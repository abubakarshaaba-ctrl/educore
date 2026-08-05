<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('staff_disciplinary_actions')) {
            Schema::table('staff_disciplinary_actions', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_disciplinary_actions', 'applied_payroll_item_id')) {
                    $table->unsignedBigInteger('applied_payroll_item_id')->nullable()->after('staff_deduction_id');
                }
                if (!Schema::hasColumn('staff_disciplinary_actions', 'applied_at')) {
                    $table->timestamp('applied_at')->nullable()->after('applied_payroll_item_id');
                }
            });
        }

        // Certificate issuance has been retired from EduCore.
        Schema::dropIfExists('certificate_issuances');
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_disciplinary_actions')) {
            Schema::table('staff_disciplinary_actions', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['applied_payroll_item_id', 'applied_at'],
                    fn (string $column) => Schema::hasColumn('staff_disciplinary_actions', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
