<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('password')->index();
            });
        }

        if (! Schema::hasColumn('users', 'password_reset_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_reset_at')->nullable()->after('must_change_password');
            });
        }

        if (! Schema::hasColumn('users', 'password_reset_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('password_reset_by')->nullable()->after('password_reset_at')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['password_reset_by', 'password_reset_at', 'must_change_password'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
