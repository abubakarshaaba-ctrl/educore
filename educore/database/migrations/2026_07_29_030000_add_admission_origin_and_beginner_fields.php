<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('admissions')) {
            return;
        }

        Schema::table('admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('admissions', 'lga_of_origin')) {
                $table->string('lga_of_origin', 100)->nullable()->after('state_of_origin');
            }
            if (!Schema::hasColumn('admissions', 'is_beginner')) {
                $table->boolean('is_beginner')->default(false)->after('lga_of_origin');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admissions')) {
            return;
        }

        Schema::table('admissions', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['lga_of_origin', 'is_beginner'],
                fn (string $column) => Schema::hasColumn('admissions', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
