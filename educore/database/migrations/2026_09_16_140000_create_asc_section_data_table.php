<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asc_section_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->integer('census_year');
            $table->char('section', 1);
            $table->json('data')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'census_year', 'section'], 'asc_section_year_unique');
            $table->index(['tenant_id', 'census_year'], 'asc_section_tenant_year_idx');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('academic_sessions')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asc_section_data');
    }
};
