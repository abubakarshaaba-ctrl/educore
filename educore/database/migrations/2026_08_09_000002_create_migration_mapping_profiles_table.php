<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('source_system', 120)->index();
            $table->string('canonical_entity', 120)->index();
            $table->string('schema_version', 20)->default('1.0');
            $table->json('mappings');
            $table->json('transformations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'source_system', 'canonical_entity'], 'mapping_profile_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_mapping_profiles');
    }
};
