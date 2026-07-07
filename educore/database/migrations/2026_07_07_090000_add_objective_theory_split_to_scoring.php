<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_types', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_types', 'objective_max')) {
                // When both are set, this assessment type is scored as
                // objective (pulled from a tagged CBT exam, read-only) +
                // theory (manual entry), summed against weight_percentage.
                $table->float('objective_max')->nullable()->after('weight_percentage');
            }
            if (!Schema::hasColumn('assessment_types', 'theory_max')) {
                $table->float('theory_max')->nullable()->after('objective_max');
            }
        });

        Schema::table('cbt_exams', function (Blueprint $table) {
            if (!Schema::hasColumn('cbt_exams', 'assessment_type_id')) {
                // Which report-card assessment slot (CA1, Exam, ...) this
                // CBT's objective score feeds into on the score entry sheet.
                $table->foreignId('assessment_type_id')->nullable()->after('term_id')
                    ->constrained('assessment_types')->nullOnDelete();
            }
        });

        Schema::table('scores', function (Blueprint $table) {
            if (!Schema::hasColumn('scores', 'objective_score')) {
                $table->float('objective_score')->nullable()->after('score');
            }
            if (!Schema::hasColumn('scores', 'theory_score')) {
                $table->float('theory_score')->nullable()->after('objective_score');
            }
            if (!Schema::hasColumn('scores', 'cbt_exam_id')) {
                $table->foreignId('cbt_exam_id')->nullable()->after('theory_score')
                    ->constrained('cbt_exams')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cbt_exam_id');
            $table->dropColumn(['objective_score', 'theory_score']);
        });
        Schema::table('cbt_exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_type_id');
        });
        Schema::table('assessment_types', function (Blueprint $table) {
            $table->dropColumn(['objective_max', 'theory_max']);
        });
    }
};
