<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'fk_acadsessions_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'is_current'], 'idx_acadsessions_tenant_current');
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->foreign('tenant_id', 'fk_terms_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('session_id', 'fk_terms_session')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'session_id'], 'idx_terms_tenant_session');
            $table->index(['tenant_id', 'is_current'], 'idx_terms_tenant_current');
        });

        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'fk_classlevels_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->enum('section', ['creche','nursery','primary','junior_secondary','senior_secondary']);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'section'], 'idx_classlevels_tenant_section');
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->foreign('tenant_id', 'fk_classarms_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_classarms_classlevel')->references('id')->on('class_levels')->cascadeOnDelete();
            $table->foreign('form_tutor_id', 'fk_classarms_formtutor')->references('id')->on('users')->nullOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->index(['tenant_id', 'class_level_id'], 'idx_classarms_tenant_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_arms');
        Schema::dropIfExists('class_levels');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_sessions');
    }
};
