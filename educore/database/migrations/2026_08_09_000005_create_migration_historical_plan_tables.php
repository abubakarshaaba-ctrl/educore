<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_historical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->nullOnDelete();
            $table->foreignId('migration_row_id')->nullable()->constrained('migration_rows')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('entity_type', 120)->index();
            $table->string('source_key', 512);
            $table->json('canonical_payload');
            $table->string('decision', 32)->index();
            $table->unsignedBigInteger('matched_record_id')->nullable();
            $table->char('payload_checksum', 64)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['migration_id', 'entity_type', 'source_key'], 'migration_history_source_unique');
            $table->index(['tenant_id', 'entity_type', 'decision'], 'migration_history_tenant_decision');
        });
        Schema::create('migration_historical_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('historical_record_id')->constrained('migration_historical_records')->cascadeOnDelete();
            $table->string('relationship_field', 160);
            $table->string('parent_entity_type', 120);
            $table->string('parent_source_key', 255);
            $table->string('parent_source', 32)->nullable();
            $table->unsignedBigInteger('resolved_record_id')->nullable();
            $table->string('resolution_status', 32)->default('unresolved')->index();
            $table->timestamps();
            $table->unique(['historical_record_id', 'relationship_field'], 'migration_history_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_historical_dependencies');
        Schema::dropIfExists('migration_historical_records');
    }
};
