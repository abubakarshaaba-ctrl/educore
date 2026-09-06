<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_reads')) {
            Schema::create('announcement_reads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('announcement_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('read_at');
                $table->timestamps();
                $table->unique(['tenant_id', 'announcement_id', 'user_id'], 'uq_announcement_read_user');
                $table->index(['tenant_id', 'user_id', 'read_at'], 'idx_announcement_read_user');
            });
        }

        if (Schema::hasTable('message_thread_replies')) {
            Schema::table('message_thread_replies', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_thread_replies', 'attachment_path')) {
                    $table->string('attachment_path')->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_name')) {
                    $table->string('attachment_name')->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_mime')) {
                    $table->string('attachment_mime', 120)->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_size')) {
                    $table->unsignedBigInteger('attachment_size')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('message_thread_replies')) {
            Schema::table('message_thread_replies', function (Blueprint $table): void {
                foreach (['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'] as $column) {
                    if (Schema::hasColumn('message_thread_replies', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('announcement_reads');
    }
};
