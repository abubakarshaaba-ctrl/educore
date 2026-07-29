<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_support_tickets')) {
            Schema::create('platform_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject', 150);
                $table->text('body');
                $table->string('status', 20)->default('open')->index();
                $table->text('admin_reply')->nullable();
                $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('replied_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('platform_broadcasts')) {
            Schema::create('platform_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->text('body');
                $table->string('target', 20)->default('all')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_broadcast_dismissals')) {
            Schema::create('platform_broadcast_dismissals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('broadcast_id')->constrained('platform_broadcasts')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->timestamp('dismissed_at');

                $table->unique(['broadcast_id', 'tenant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_broadcast_dismissals');
        Schema::dropIfExists('platform_broadcasts');
        Schema::dropIfExists('platform_support_tickets');
    }
};
