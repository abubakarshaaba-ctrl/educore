<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CbtQuestion
 *
 * Supports multiple question types:
 *  - mcq         : Multiple choice A/B/C/D
 *  - essay       : Long-form written answer (manually graded)
 *  - short_answer: Brief written answer (manually or keyword graded)
 *  - fill_blank  : Fill in the blank
 *  - true_false  : True or False
 *
 * Column notes:
 *  option_a/b/c/d           — flat text options for MCQ / true_false
 *  correct_answer_letter    — 'a','b','c','d' for MCQ; 'a'=True/'b'=False for true_false
 *  correct_option           — legacy tinyint (now nullable, kept for backward compat)
 *  options                  — legacy JSON array (nullable, kept for backward compat)
 *  model_answer             — reference answer for essay/short_answer marking
 */
class CbtQuestion extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'question_bank_id',
        'type',                   // mcq | essay | short_answer | fill_blank | true_false
        'question_text',
        'question_html',          // rich text version (optional)
        'image_path',
        // MCQ options
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer_letter',  // a | b | c | d (new string column)
        // Legacy columns (kept nullable for backward compat)
        'options',
        'correct_option',
        // Common
        'explanation',
        'difficulty',
        'marks',
        // Essay / short answer
        'word_limit',
        'model_answer',
    ];

    protected function casts(): array
    {
        return [
            'options'    => 'array',
            'marks'      => 'float',
            'difficulty' => 'integer',
            'word_limit' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(CbtQuestionBank::class, 'question_bank_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function isMcq(): bool         { return $this->type === 'mcq'; }
    public function isEssay(): bool       { return $this->type === 'essay'; }
    public function isShortAnswer(): bool { return $this->type === 'short_answer'; }
    public function isFillBlank(): bool   { return $this->type === 'fill_blank'; }
    public function isTrueFalse(): bool   { return $this->type === 'true_false'; }
    public function isAutoGraded(): bool  { return in_array($this->type, ['mcq','fill_blank','true_false']); }
    public function isManualGraded(): bool{ return in_array($this->type, ['essay','short_answer']); }

    /**
     * Returns the correct answer display text.
     * Works with both new (correct_answer_letter) and legacy (correct_option) columns.
     */
    public function getCorrectAnswerTextAttribute(): string
    {
        $letter = $this->correct_answer_letter
            ?? ($this->correct_option ? chr(96 + $this->correct_option) : null); // 1→a, 2→b, etc.

        if (!$letter) return '';

        // Map letter to option text
        $map = [
            'a' => $this->option_a ?? ($this->options['a'] ?? null),
            'b' => $this->option_b ?? ($this->options['b'] ?? null),
            'c' => $this->option_c ?? ($this->options['c'] ?? null),
            'd' => $this->option_d ?? ($this->options['d'] ?? null),
        ];

        $text = $map[strtolower($letter)] ?? '';
        return $text ? strtoupper($letter) . '. ' . $text : strtoupper($letter);
    }

    /**
     * Checks if a given student answer is correct (for auto-graded types).
     */
    public function isCorrect(string $studentAnswer): bool
    {
        if (!$this->isAutoGraded()) return false;

        $correct = strtolower(trim(
            $this->correct_answer_letter
                ?? ($this->correct_option ? chr(96 + $this->correct_option) : '')
        ));

        return strtolower(trim($studentAnswer)) === $correct;
    }

    /**
     * Returns all options as array: ['a' => 'Option text', ...].
     * Handles both flat columns and legacy JSON options.
     */
    public function optionsArray(): array
    {
        $opts = [];
        foreach (['a','b','c','d'] as $letter) {
            $col = 'option_' . $letter;
            $text = $this->$col
                ?? ($this->options[$letter] ?? null);
            if ($text !== null && $text !== '') {
                $opts[$letter] = $text;
            }
        }
        return $opts;
    }

    /**
     * Type badge color for UI.
     */
    public function typeBadgeColor(): array
    {
        return match($this->type ?? 'mcq') {
            'mcq'          => ['#EFF6FF', '#2563EB'],
            'essay'        => ['#F0FDF4', '#059669'],
            'short_answer' => ['#FFFBEB', '#D97706'],
            'fill_blank'   => ['#F5F3FF', '#7C3AED'],
            'true_false'   => ['#FEF2F2', '#DC2626'],
            default        => ['#F1F5F9', '#64748B'],
        };
    }

    public function typeLabel(): string
    {
        return match($this->type ?? 'mcq') {
            'mcq'          => 'MCQ',
            'essay'        => 'Essay',
            'short_answer' => 'Short Answer',
            'fill_blank'   => 'Fill in Blank',
            'true_false'   => 'True / False',
            default        => 'MCQ',
        };
    }
}
