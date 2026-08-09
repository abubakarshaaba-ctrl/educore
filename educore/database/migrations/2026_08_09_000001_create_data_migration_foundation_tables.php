<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 48)->unique();
            $table->string('direction', 16)->index();
            $table->string('migration_type', 32)->index();
            $table->string('source_system', 120)->nullable();
            $table->string('destination_system', 120)->nullable();
            $table->string('status', 40)->index();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('total_datasets')->default(0);
            $table->unsignedBigInteger('total_source_rows')->default(0);
            $table->unsignedBigInteger('total_valid_rows')->default(0);
            $table->unsignedBigInteger('total_created')->default(0);
            $table->unsignedBigInteger('total_updated')->default(0);
            $table->unsignedBigInteger('total_skipped')->default(0);
            $table->unsignedBigInteger('total_failed')->default(0);
            $table->char('checksum', 64)->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('migration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('sanitized_filename', 255);
            $table->string('mime_type', 160)->nullable();
            $table->string('detected_file_type', 32)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64)->index();
            $table->string('storage_disk', 64);
            $table->string('storage_path', 1024)->unique();
            $table->string('parser_version', 40)->default('1.0');
            $table->boolean('is_original')->default(true);
            $table->timestamp('uploaded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['migration_id', 'sha256', 'original_filename'], 'migration_files_source_unique');
            $table->index(['tenant_id', 'migration_id']);
        });

        Schema::create('migration_datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('migration_file_id')->nullable()->constrained('migration_files')->nullOnDelete();
            $table->string('source_name', 255);
            $table->string('canonical_entity', 120)->nullable()->index();
            $table->string('classification_status', 32)->default('unclassified');
            $table->decimal('classification_confidence', 5, 2)->nullable();
            $table->unsignedBigInteger('source_row_count')->default(0);
            $table->unsignedBigInteger('staged_row_count')->default(0);
            $table->json('source_schema')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['migration_id', 'classification_status']);
        });

        Schema::create('migration_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained('migration_datasets')->cascadeOnDelete();
            $table->unsignedBigInteger('row_number');
            $table->string('source_identifier', 255)->nullable();
            $table->json('raw_payload');
            $table->json('mapped_payload')->nullable();
            $table->json('normalised_payload')->nullable();
            $table->string('validation_status', 32)->default('pending')->index();
            $table->decimal('mapping_confidence', 5, 2)->nullable();
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->json('resolved_entity_ids')->nullable();
            $table->char('source_record_checksum', 64)->index();
            $table->char('destination_record_checksum', 64)->nullable()->index();
            $table->timestamps();

            $table->unique(['dataset_id', 'row_number']);
            $table->index(['migration_id', 'validation_status']);
        });

        Schema::create('migration_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->cascadeOnDelete();
            $table->string('source_column', 255);
            $table->string('destination_entity', 120)->nullable();
            $table->string('destination_field', 160)->nullable();
            $table->string('decision', 32)->default('unmapped')->index();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('transformation_rule')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['migration_id', 'dataset_id', 'source_column'], 'migration_mapping_source_unique');
        });

        Schema::create('migration_entity_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->string('source_system', 120);
            $table->string('entity_type', 120)->index();
            $table->string('source_table', 255)->nullable();
            $table->string('source_record_id', 255);
            $table->unsignedBigInteger('educore_record_id')->nullable();
            $table->string('destination_record_id', 255)->nullable();
            $table->string('source_business_identifier', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'source_system', 'entity_type', 'source_record_id'], 'migration_entity_source_unique');
            $table->index(['tenant_id', 'entity_type', 'educore_record_id'], 'migration_entity_reverse_lookup');
        });

        Schema::create('migration_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->cascadeOnDelete();
            $table->foreignId('migration_row_id')->nullable()->constrained('migration_rows')->cascadeOnDelete();
            $table->foreignId('migration_file_id')->nullable()->constrained('migration_files')->cascadeOnDelete();
            $table->string('severity', 16)->index();
            $table->string('category', 80)->index();
            $table->string('field', 160)->nullable();
            $table->text('source_value')->nullable();
            $table->text('message');
            $table->text('suggested_resolution')->nullable();
            $table->string('status', 24)->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['migration_id', 'severity', 'status']);
        });

        Schema::create('migration_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('migration_datasets')->cascadeOnDelete();
            $table->string('stage', 64);
            $table->unsignedBigInteger('last_row_number')->nullable();
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->char('checkpoint_checksum', 64)->nullable();
            $table->json('state')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['migration_id', 'stage']);
        });

        Schema::create('migration_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('approval_type', 48);
            $table->string('decision', 24)->index();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->json('approved_snapshot')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['migration_id', 'approval_type']);
        });

        Schema::create('migration_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $table->string('scope', 120);
            $table->unsignedBigInteger('source_count')->nullable();
            $table->unsignedBigInteger('destination_count')->nullable();
            $table->decimal('source_total', 20, 4)->nullable();
            $table->decimal('destination_total', 20, 4)->nullable();
            $table->string('status', 24)->index();
            $table->json('details')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['migration_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_reconciliations');
        Schema::dropIfExists('migration_approvals');
        Schema::dropIfExists('migration_checkpoints');
        Schema::dropIfExists('migration_issues');
        Schema::dropIfExists('migration_entity_links');
        Schema::dropIfExists('migration_mappings');
        Schema::dropIfExists('migration_rows');
        Schema::dropIfExists('migration_datasets');
        Schema::dropIfExists('migration_files');
        Schema::dropIfExists('data_migrations');
    }
};
