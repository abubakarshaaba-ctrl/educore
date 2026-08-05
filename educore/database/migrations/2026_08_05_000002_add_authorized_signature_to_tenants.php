<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'authorized_signature_path')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('authorized_signature_path')->nullable()->after('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'authorized_signature_path')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('authorized_signature_path');
            });
        }
    }
};
