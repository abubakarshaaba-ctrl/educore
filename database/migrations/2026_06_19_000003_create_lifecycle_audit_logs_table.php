<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id', 'fk_auditlogs_tenant')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('actor_user_id', 'fk_auditlogs_actor')->references('id')->on('users')->nullOnDelete();

            $table->index('tenant_id', 'idx_auditlogs_tenant');
            $table->index('actor_user_id', 'idx_auditlogs_actor');
            $table->index(['auditable_type', 'auditable_id'], 'idx_auditlogs_auditable');
            $table->index('action', 'idx_auditlogs_action');
            $table->index('created_at', 'idx_auditlogs_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
