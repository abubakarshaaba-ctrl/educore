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

        /*
        |--------------------------------------------------------------------------
        | Make legacy CBT MCQ columns compatible with essay/short-answer questions
        |--------------------------------------------------------------------------
        | The original cbt_questions table created `options` as NOT NULL JSON and
        | `correct_option` as NOT NULL unsignedTinyInteger. That works for MCQ but
        | breaks essay/short-answer imports where no A/B/C/D option exists.
        */
        if (Schema::hasColumn('cbt_questions', 'correct_option')) {
            DB::statement("ALTER TABLE `cbt_questions` MODIFY `correct_option` TINYINT UNSIGNED NULL");
        }

        if (Schema::hasColumn('cbt_questions', 'options')) {
            DB::statement("ALTER TABLE `cbt_questions` MODIFY `options` JSON NULL");
        }

        // ── Extend cbt_questions for richer question types ─────────────
        Schema::table('cbt_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('cbt_questions', 'type')) {
                $table->enum('type', ['mcq', 'essay', 'short_answer', 'fill_blank', 'true_false'])
                    ->default('mcq')
                    ->after('question_bank_id');
            }

            if (!Schema::hasColumn('cbt_questions', 'question_html')) {
                $table->longText('question_html')->nullable()->after('question_text');
            }

            if (!Schema::hasColumn('cbt_questions', 'image_path')) {
                $table->string('image_path')->nullable();
            }

            if (!Schema::hasColumn('cbt_questions', 'marks')) {
                $table->decimal('marks', 5, 2)->default(1);
            }

            if (!Schema::hasColumn('cbt_questions', 'word_limit')) {
                $table->integer('word_limit')->nullable();
            }

            if (!Schema::hasColumn('cbt_questions', 'model_answer')) {
                $table->text('model_answer')->nullable();
            }
        });

        // ── Extend cbt_student_sessions for essay answers ──────────────
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
        // Intentionally left non-destructive.
        // Reverting correct_option/options to NOT NULL may break existing essay
        // and short-answer records already imported into cbt_questions.
    }
};
