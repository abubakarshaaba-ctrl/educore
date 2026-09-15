<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('assessment_template_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('assessment_template_id');
            $table->string('name', 100);
            $table->decimal('weight_percentage', 5, 2);
            $table->string('component_type', 30)->default('coursework');
            $table->string('entry_mode', 30)->default('manual');
            $table->decimal('objective_max', 5, 2)->nullable();
            $table->decimal('theory_max', 5, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'assessment_template_id']);
            $table->foreign('assessment_template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
        });

        Schema::create('assessment_template_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('assessment_template_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('session_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'class_level_id', 'session_id'], 'assessment_template_assignment_unique');
            $table->index(['tenant_id', 'assessment_template_id', 'is_active'], 'assessment_template_assignment_lookup');
            $table->foreign('assessment_template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_template_assignments');
        Schema::dropIfExists('assessment_template_components');
        Schema::dropIfExists('assessment_templates');
    }
};
