<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_broadcasts') && ! Schema::hasColumn('platform_broadcasts', 'image_path')) {
            Schema::table('platform_broadcasts', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('body');
            });
        }

        if (Schema::hasTable('announcements') && ! Schema::hasColumn('announcements', 'image_path')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'image_path')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }

        if (Schema::hasTable('platform_broadcasts') && Schema::hasColumn('platform_broadcasts', 'image_path')) {
            Schema::table('platform_broadcasts', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
};
