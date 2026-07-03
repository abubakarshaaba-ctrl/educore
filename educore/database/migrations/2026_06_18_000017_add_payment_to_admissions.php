<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('admissions', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('portal_token');
            }
            if (!Schema::hasColumn('admissions', 'payment_status')) {
                $table->enum('payment_status', ['not_required','pending','paid','waived'])
                      ->default('not_required')->after('payment_reference');
            }
        });
    }
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumnIfExists('payment_reference');
            $table->dropColumnIfExists('payment_status');
        });
    }
};
