<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_threads')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_threads', 'conversation_type')) {
                    $table->string('conversation_type', 40)->default('student');
                }
                if (! Schema::hasColumn('message_threads', 'recipient_user_id')) {
                    $table->unsignedBigInteger('recipient_user_id')->nullable();
                }
                if (! Schema::hasColumn('message_threads', 'audience')) {
                    $table->string('audience', 40)->nullable();
                }
            });

            if (Schema::hasColumn('message_threads', 'student_id')) {
                $driver = DB::connection()->getDriverName();

                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE message_threads MODIFY student_id BIGINT UNSIGNED NULL');
                } elseif ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE message_threads ALTER COLUMN student_id DROP NOT NULL');
                }
            }
        }

        if (Schema::hasTable('message_thread_replies')) {
            Schema::table('message_thread_replies', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_thread_replies', 'attachment_path')) {
                    $table->string('attachment_path', 500)->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_name')) {
                    $table->string('attachment_name', 180)->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_mime')) {
                    $table->string('attachment_mime', 120)->nullable();
                }
                if (! Schema::hasColumn('message_thread_replies', 'attachment_size')) {
                    $table->unsignedBigInteger('attachment_size')->nullable();
                }
            });
        }

        if (! Schema::hasTable('message_thread_reads')) {
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
    }

    public function down(): void
    {
        // Intentional no-op: this migration repairs production schema drift.
    }
};
