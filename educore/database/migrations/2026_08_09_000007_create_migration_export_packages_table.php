<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_export_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_id')->nullable()->constrained('data_migrations')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('export_type', 24);
            $table->string('schema_version', 20);
            $table->string('package_format_version', 20);
            $table->string('storage_disk', 64);
            $table->string('storage_path', 1024)->unique();
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64)->index();
            $table->json('manifest');
            $table->json('scope');
            $table->string('status', 24)->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_export_packages');
    }
};
