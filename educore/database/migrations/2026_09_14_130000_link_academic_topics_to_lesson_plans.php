<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->text('entry_behaviour')->nullable()->after('sex');
            $table->unsignedBigInteger('academic_topic_id')->nullable()->after('entry_behaviour');
            $table->index(['teacher_id', 'academic_topic_id'], 'lesson_plan_academic_topic_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropIndex('lesson_plan_academic_topic_idx');
            $table->dropColumn(['entry_behaviour', 'academic_topic_id']);
        });
    }
};
