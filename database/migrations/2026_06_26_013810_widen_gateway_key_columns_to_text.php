<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            $table->text('public_key')->nullable()->change();
            $table->text('secret_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            $table->string('public_key')->nullable()->change();
            $table->string('secret_key')->nullable()->change();
        });
    }
};
