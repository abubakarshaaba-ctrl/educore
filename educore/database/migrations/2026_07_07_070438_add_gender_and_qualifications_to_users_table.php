<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fixes a live 500 error: StaffController::update() and the User model have
// both referenced these columns (gender in validation/fillable, qualifications
// as a fillable array-cast field) since they were added, but no migration
// ever created them — any staff profile edit submitting either field fails.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 10)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'qualifications')) {
                $table->json('qualifications')->nullable()->after('qualification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'gender')) $table->dropColumn('gender');
            if (Schema::hasColumn('users', 'qualifications')) $table->dropColumn('qualifications');
        });
    }
};
