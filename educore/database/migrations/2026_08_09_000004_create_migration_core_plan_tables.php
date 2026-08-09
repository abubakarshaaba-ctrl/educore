<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_core_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->nullOnDelete();
            $table->foreignId('migration_row_id')->nullable()->constrained('migration_rows')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('entity_type', 120)->index();
            $table->string('source_key', 255);
            $table->json('canonical_payload');
            $table->string('decision', 32)->index();
            $table->unsignedBigInteger('matched_record_id')->nullable();
            $table->char('payload_checksum', 64)->index();
            $table->json('match_candidates')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['migration_id', 'entity_type', 'source_key'], 'migration_core_source_unique');
            $table->index(['tenant_id', 'entity_type', 'decision'], 'migration_core_tenant_decision');
        });

        Schema::create('migration_core_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('core_record_id')->constrained('migration_core_records')->cascadeOnDelete();
            $table->string('relationship_field', 160);
            $table->string('parent_entity_type', 120);
            $table->string('parent_source_key', 255);
            $table->string('parent_source', 32)->nullable();
            $table->unsignedBigInteger('resolved_record_id')->nullable();
            $table->string('resolution_status', 32)->default('unresolved')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['core_record_id', 'relationship_field'], 'migration_core_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_core_dependencies');
        Schema::dropIfExists('migration_core_records');
    }
};
