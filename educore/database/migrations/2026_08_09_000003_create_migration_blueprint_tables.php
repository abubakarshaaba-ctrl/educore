<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_blueprint_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('entity_type', 120)->index();
            $table->string('source_key', 255);
            $table->json('canonical_payload');
            $table->string('decision', 32)->index();
            $table->decimal('confidence', 5, 2)->default(0);
            $table->unsignedBigInteger('matched_record_id')->nullable();
            $table->char('payload_checksum', 64)->index();
            $table->json('match_candidates')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['migration_id', 'entity_type', 'source_key'], 'migration_blueprint_source_unique');
            $table->index(['tenant_id', 'entity_type', 'decision'], 'migration_blueprint_tenant_lookup');
        });

        Schema::create('migration_blueprint_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('migration_blueprint_nodes')->cascadeOnDelete();
            $table->string('relationship_field', 160);
            $table->string('parent_entity_type', 120);
            $table->string('parent_source_key', 255);
            $table->foreignId('parent_node_id')->nullable()->constrained('migration_blueprint_nodes')->nullOnDelete();
            $table->string('resolution_status', 32)->default('unresolved')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['node_id', 'relationship_field'], 'migration_blueprint_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_blueprint_dependencies');
        Schema::dropIfExists('migration_blueprint_nodes');
    }
};
