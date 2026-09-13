<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_scheme_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id', 'fk_assessment_scheme_templates_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name'], 'uq_assessment_scheme_template_name');
        });

        Schema::create('assessment_scheme_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('assessment_scheme_template_id');
            $table->string('name', 100);
            $table->unsignedTinyInteger('weight_percentage');
            $table->float('objective_max')->nullable();
            $table->float('theory_max')->nullable();
            $table->boolean('is_exam')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id', 'fk_assessment_scheme_items_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('assessment_scheme_template_id', 'fk_assessment_scheme_items_template')
                ->references('id')->on('assessment_scheme_templates')->cascadeOnDelete();
            $table->index(
                ['tenant_id', 'assessment_scheme_template_id'],
                'idx_assessment_scheme_items_template'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scheme_template_items');
        Schema::dropIfExists('assessment_scheme_templates');
    }
};
