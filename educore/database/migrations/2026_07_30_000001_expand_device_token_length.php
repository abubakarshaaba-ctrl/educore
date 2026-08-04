<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE device_tokens MODIFY token VARCHAR(512) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('device_tokens') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE device_tokens MODIFY token VARCHAR(255) NOT NULL');
        }
    }
};
