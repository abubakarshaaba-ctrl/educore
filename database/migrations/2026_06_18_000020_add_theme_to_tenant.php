<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'theme_primary'))
                $table->string('theme_primary', 7)->default('#071E45')->after('logo_path');
            if (!Schema::hasColumn('tenants', 'theme_accent'))
                $table->string('theme_accent', 7)->default('#D79A21')->after('theme_primary');
            if (!Schema::hasColumn('tenants', 'theme_sidebar'))
                $table->string('theme_sidebar', 7)->default('#071E45')->after('theme_accent');
        });
    }
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumnIfExists('theme_primary');
            $table->dropColumnIfExists('theme_accent');
            $table->dropColumnIfExists('theme_sidebar');
        });
    }
};
