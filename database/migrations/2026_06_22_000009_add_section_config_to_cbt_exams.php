<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds per-section configuration to CBT exams:
     *
     * Section A — Objective questions (MCQ, True/False, Fill-in-Blank)
     *   section_objective_count      How many objective questions to draw from the bank
     *   section_objective_marks      Marks per objective question (e.g. 1.0 or 0.5)
     *
     * Section B — Theory questions (Short Answer, Essay)
     *   section_theory_count         How many theory questions to draw from the bank
     *   section_theory_marks         Marks per theory question (e.g. 5.0 or 10.0)
     *
     * Both sections are optional — if section_objective_count is 0 or null,
     * Section A is skipped; same for theory. The legacy total_questions field
     * becomes a computed sum (objective + theory) and is retained for backward compat.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cbt_exams')) {
            return;
        }

        Schema::table('cbt_exams', function (Blueprint $table) {
            if (!Schema::hasColumn('cbt_exams', 'section_objective_count')) {
                $table->integer('section_objective_count')->default(0)->after('total_questions');
            }
            if (!Schema::hasColumn('cbt_exams', 'section_objective_marks')) {
                $table->decimal('section_objective_marks', 5, 2)->default(1.00)->after('section_objective_count');
            }
            if (!Schema::hasColumn('cbt_exams', 'section_theory_count')) {
                $table->integer('section_theory_count')->default(0)->after('section_objective_marks');
            }
            if (!Schema::hasColumn('cbt_exams', 'section_theory_marks')) {
                $table->decimal('section_theory_marks', 5, 2)->default(5.00)->after('section_theory_count');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cbt_exams')) {
            return;
        }

        Schema::table('cbt_exams', function (Blueprint $table) {
            foreach ([
                'section_objective_count', 'section_objective_marks',
                'section_theory_count', 'section_theory_marks',
            ] as $col) {
                if (Schema::hasColumn('cbt_exams', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
