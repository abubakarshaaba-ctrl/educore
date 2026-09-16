<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asc_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedInteger('census_year');
            $table->date('reference_date');
            $table->string('status', 30)->default('draft');

            // Immutable-at-finalisation census payloads. Auto data is rebuilt from
            // operational records only while the return is still a draft.
            $table->json('auto_data')->nullable();
            $table->json('manual_data')->nullable();
            $table->json('completeness')->nullable();
            $table->json('reconciliation')->nullable();

            $table->timestamp('synchronized_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'census_year'], 'uq_asc_returns_tenant_year');
            $table->index(['tenant_id', 'status'], 'idx_asc_returns_tenant_status');
            $table->index(['tenant_id', 'reference_date'], 'idx_asc_returns_tenant_reference');

            $table->foreign('tenant_id', 'fk_asc_returns_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_asc_returns_session')
                ->references('id')->on('academic_sessions')->nullOnDelete();
            $table->foreign('finalized_by', 'fk_asc_returns_finalized_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('submitted_by', 'fk_asc_returns_submitted_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asc_returns');
    }
};
