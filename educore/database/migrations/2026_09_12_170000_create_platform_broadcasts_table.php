<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->string('target', 30)->default('all');
            $table->unsignedInteger('tenant_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->index(['target', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_broadcasts');
    }
};
