<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_type_class_level')) {
            return;
        }

        Schema::create('assessment_type_class_level', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_type_id');
            $table->unsignedBigInteger('class_level_id');
            $table->timestamps();

            $table->foreign('assessment_type_id', 'fk_atcl_assessment_type')
                ->references('id')->on('assessment_types')->cascadeOnDelete();
            $table->foreign('class_level_id', 'fk_atcl_class_level')
                ->references('id')->on('class_levels')->cascadeOnDelete();

            $table->unique(
                ['assessment_type_id', 'class_level_id'],
                'uq_atcl_assessment_class_level'
            );
            $table->index('class_level_id', 'idx_atcl_class_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_type_class_level');
    }
};
