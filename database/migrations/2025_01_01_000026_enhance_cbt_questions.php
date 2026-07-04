<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cbt_questions')) {
            return;
        }

        Schema::table('cbt_questions', function (Blueprint $table) {
            if (Schema::hasColumn('cbt_questions', 'correct_option')) {
                $table->unsignedTinyInteger('correct_option')->nullable()->change();
            }

            if (Schema::hasColumn('cbt_questions', 'options')) {
                $table->json('options')->nullable()->change();
            }

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
        // Intentionally non-destructive: making these columns NOT NULL again
        // could invalidate existing essay and short-answer records.
    }
};
