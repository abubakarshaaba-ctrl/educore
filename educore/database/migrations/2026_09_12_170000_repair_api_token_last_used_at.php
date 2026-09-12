<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_tokens') || Schema::hasColumn('api_tokens', 'last_used_at')) {
            return;
        }

        Schema::table('api_tokens', function (Blueprint $table): void {
            $table->timestamp('last_used_at')->nullable();
        });
    }

    public function down(): void
    {
        // Deliberately irreversible: older installations may have owned this
        // column before the repair migration was introduced.
    }
};
