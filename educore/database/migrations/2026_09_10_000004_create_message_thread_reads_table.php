<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_thread_reads')) {
            return;
        }

        Schema::create('message_thread_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id'], 'uq_msg_thread_reads');
            $table->index(['tenant_id', 'user_id'], 'idx_msg_reads_tenant_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_thread_reads');
    }
};
