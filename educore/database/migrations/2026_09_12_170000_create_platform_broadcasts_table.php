<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // platform_broadcasts was introduced by an earlier platform-support
        // migration. Upgrade that table in place when it already exists so
        // existing installations do not fail with "table already exists".
        if (! Schema::hasTable('platform_broadcasts')) {
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
        } else {
            if (! Schema::hasColumn('platform_broadcasts', 'tenant_count')) {
                Schema::table('platform_broadcasts', function (Blueprint $table) {
                    $table->unsignedInteger('tenant_count')->default(0)->after('target');
                });
            }

            if (! Schema::hasColumn('platform_broadcasts', 'expired_at')) {
                Schema::table('platform_broadcasts', function (Blueprint $table) {
                    $table->timestamp('expired_at')->nullable()->after('expires_at');
                });
            }
        }

        if (Schema::hasTable('announcements') && ! Schema::hasColumn('announcements', 'platform_broadcast_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->foreignId('platform_broadcast_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('platform_broadcasts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Restore the schema owned by the earlier platform-support migration;
        // do not drop platform_broadcasts itself because that table predates
        // this upgrade migration.
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'platform_broadcast_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('platform_broadcast_id');
            });
        }

        if (Schema::hasTable('platform_broadcasts')) {
            if (Schema::hasColumn('platform_broadcasts', 'expired_at')) {
                Schema::table('platform_broadcasts', function (Blueprint $table) {
                    $table->dropColumn('expired_at');
                });
            }

            if (Schema::hasColumn('platform_broadcasts', 'tenant_count')) {
                Schema::table('platform_broadcasts', function (Blueprint $table) {
                    $table->dropColumn('tenant_count');
                });
            }
        }
    }
};
