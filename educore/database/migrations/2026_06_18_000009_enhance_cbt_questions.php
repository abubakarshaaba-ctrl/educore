<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cbt_questions')) {
            return;
        }

        // ── Step 1: Make legacy NOT NULL columns nullable ────────────────
        // MODIFY is MySQL-only syntax; SQLite (test suite) columns are already
        // dynamically typed/nullable-by-default, so there's nothing to widen there.
        if (DB::connection()->getDriverName() === 'mysql') {
            // correct_option is NOT NULL — breaks essay/short_answer questions
            if (Schema::hasColumn('cbt_questions', 'correct_option')) {
                DB::statement("ALTER TABLE `cbt_questions` MODIFY `correct_option` TINYINT UNSIGNED NULL");
            }

            // options JSON was NOT NULL — breaks essay questions with no options
            if (Schema::hasColumn('cbt_questions', 'options')) {
                DB::statement("ALTER TABLE `cbt_questions` MODIFY `options` JSON NULL");
            }
        }

        // ── Step 2: Add new columns for richer question types ────────────
        Schema::table('cbt_questions', function (Blueprint $table) {
            // Question type
            if (!Schema::hasColumn('cbt_questions', 'type')) {
                $table->enum('type', ['mcq','essay','short_answer','fill_blank','true_false'])
                      ->default('mcq')
                      ->after('question_bank_id');
            }

            // Separate flat option columns (option_a / option_b / option_c / option_d)
            // These are simpler than the JSON options column for most use cases
            if (!Schema::hasColumn('cbt_questions', 'option_a')) {
                $table->text('option_a')->nullable()->after('question_text');
            }
            if (!Schema::hasColumn('cbt_questions', 'option_b')) {
                $table->text('option_b')->nullable()->after('option_a');
            }
            if (!Schema::hasColumn('cbt_questions', 'option_c')) {
                $table->text('option_c')->nullable()->after('option_b');
            }
            if (!Schema::hasColumn('cbt_questions', 'option_d')) {
                $table->text('option_d')->nullable()->after('option_c');
            }

            // correct_option as string letter: a/b/c/d (replaces tinyint)
            // The MODIFY above made it nullable; rename handled at app layer
            // We add correct_answer_letter as the new string column
            if (!Schema::hasColumn('cbt_questions', 'correct_answer_letter')) {
                $table->string('correct_answer_letter', 1)->nullable(); // a, b, c, d
            }

            // Rich text version of question
            if (!Schema::hasColumn('cbt_questions', 'question_html')) {
                $table->longText('question_html')->nullable();
            }

            // Image attachment
            if (!Schema::hasColumn('cbt_questions', 'image_path')) {
                $table->string('image_path', 255)->nullable();
            }

            // Per-question marks
            if (!Schema::hasColumn('cbt_questions', 'marks')) {
                $table->decimal('marks', 5, 2)->default(1);
            }

            // Essay specific
            if (!Schema::hasColumn('cbt_questions', 'word_limit')) {
                $table->unsignedSmallInteger('word_limit')->nullable();
            }
            if (!Schema::hasColumn('cbt_questions', 'model_answer')) {
                $table->text('model_answer')->nullable();
            }
        });

        // ── Step 3: Essay answers in student sessions ────────────────────
        if (Schema::hasTable('cbt_student_sessions')) {
            Schema::table('cbt_student_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('cbt_student_sessions', 'essay_answers')) {
                    $table->json('essay_answers')->nullable();
                }
                if (!Schema::hasColumn('cbt_student_sessions', 'marked_by')) {
                    $table->unsignedBigInteger('marked_by')->nullable();
                }
                if (!Schema::hasColumn('cbt_student_sessions', 'manual_scores')) {
                    $table->json('manual_scores')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive — do not revert nullable columns
        // as existing essay records require NULL values
    }
};
