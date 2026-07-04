<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g., "Greenfield Academy"
            $table->string('slug')->unique();                // e.g., "greenfield-academy"
            $table->string('subdomain')->unique()->nullable(); // e.g., "greenfield.sms.ng"
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'suspended', 'subscription_expired', 'pending'])->default('pending');
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
