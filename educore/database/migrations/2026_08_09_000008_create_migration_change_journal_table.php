<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_change_journals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->string('entity_type', 120)->index();
            $t->unsignedBigInteger('entity_id');
            $t->string('classification', 32)->index();
            $t->string('operation', 24);
            $t->unsignedBigInteger('sequence');
            $t->json('before_image')->nullable();
            $t->json('after_image')->nullable();
            $t->char('before_checksum', 64)->nullable();
            $t->char('after_checksum', 64)->nullable();
            $t->string('rollback_status', 32)->default('pending')->index();
            $t->string('compensation_strategy', 160)->nullable();
            $t->text('rollback_error')->nullable();
            $t->timestamp('rolled_back_at')->nullable();
            $t->timestamps();
            $t->unique(['migration_id', 'entity_type', 'entity_id', 'sequence'], 'migration_journal_sequence_unique');
            $t->index(['migration_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_change_journals');
    }
};
