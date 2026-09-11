<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('platform_broadcasts')) {
            Schema::table('platform_broadcasts', function (Blueprint $table): void {
                if (! Schema::hasColumn('platform_broadcasts', 'audience')) {
                    $table->string('audience', 32)->default('staff')->index()->after('target');
                }
                if (! Schema::hasColumn('platform_broadcasts', 'priority')) {
                    $table->string('priority', 16)->default('normal')->index()->after('audience');
                }
            });
        }

        if (! Schema::hasTable('platform_broadcast_user_reads')) {
            Schema::create('platform_broadcast_user_reads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('broadcast_id')->constrained('platform_broadcasts')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamps();

                $table->unique(['broadcast_id', 'user_id'], 'platform_broadcast_user_unique');
                $table->index(['tenant_id', 'user_id', 'dismissed_at'], 'platform_broadcast_user_feed_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_broadcast_user_reads');

        if (Schema::hasTable('platform_broadcasts')) {
            Schema::table('platform_broadcasts', function (Blueprint $table): void {
                foreach (['priority', 'audience'] as $column) {
                    if (Schema::hasColumn('platform_broadcasts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
