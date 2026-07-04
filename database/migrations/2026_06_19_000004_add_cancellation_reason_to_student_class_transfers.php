<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_class_transfers')
            && ! Schema::hasColumn('student_class_transfers', 'cancellation_reason')) {
            Schema::table('student_class_transfers', function (Blueprint $table) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_class_transfers', 'cancellation_reason')) {
            Schema::table('student_class_transfers', function (Blueprint $table) {
                $table->dropColumn('cancellation_reason');
            });
        }
    }
};
