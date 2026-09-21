<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_threads')) {
            return;
        }

        if (! Schema::hasColumn('message_threads', 'conversation_type')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->string('conversation_type', 40)->default('student')->after('student_id');
            });
        }

        if (! Schema::hasColumn('message_threads', 'recipient_user_id')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('conversation_type');
            });
        }

        if (! Schema::hasColumn('message_threads', 'audience')) {
            Schema::table('message_threads', function (Blueprint $table): void {
                $table->string('audience', 40)->nullable()->after('recipient_user_id');
            });
        }

        // Broadcast and internal conversations do not belong to one student.
        // This ALTER is intentionally repeated in a new migration because some
        // production databases had the earlier migration recorded while the
        // column itself remained NOT NULL.
        if (Schema::hasColumn('message_threads', 'student_id')) {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE message_threads MODIFY student_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE message_threads ALTER COLUMN student_id DROP NOT NULL');
            } elseif ($driver !== 'sqlite') {
                Schema::table('message_threads', function (Blueprint $table): void {
                    $table->unsignedBigInteger('student_id')->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('message_thread_replies')) {
            if (! Schema::hasColumn('message_thread_replies', 'is_read')) {
                Schema::table('message_thread_replies', function (Blueprint $table): void {
                    $table->boolean('is_read')->default(false);
                });
            }

            if (! Schema::hasColumn('message_thread_replies', 'read_at')) {
                Schema::table('message_thread_replies', function (Blueprint $table): void {
                    $table->timestamp('read_at')->nullable();
                });
            }

            foreach ([
                'attachment_path' => ['string', 500],
                'attachment_name' => ['string', 180],
                'attachment_mime' => ['string', 120],
            ] as $column => [$type, $length]) {
                if (! Schema::hasColumn('message_thread_replies', $column)) {
                    Schema::table('message_thread_replies', function (Blueprint $table) use ($column, $length): void {
                        $table->string($column, $length)->nullable();
                    });
                }
            }

            if (! Schema::hasColumn('message_thread_replies', 'attachment_size')) {
                Schema::table('message_thread_replies', function (Blueprint $table): void {
                    $table->unsignedBigInteger('attachment_size')->nullable();
                });
            }
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
        // Production repair only. Do not recreate the historical NOT NULL
        // student_id constraint or remove communication data on rollback.
    }
};
