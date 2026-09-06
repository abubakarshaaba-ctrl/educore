<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('scope', 100);
            $table->uuid('request_id');
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing');
            $table->longText('response_json')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'scope', 'request_id'], 'mobile_idempotency_unique');
            $table->index(['tenant_id', 'created_at'], 'mobile_idempotency_tenant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_idempotency_keys');
    }
};
