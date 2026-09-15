<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'student_id',
        'subject_id',
        'assessment_type_id',
        'term_id',
        'session_id',
        'entered_by',
        'score',
        'objective_score',
        'theory_score',
        'cbt_exam_id',
        'entered_at',
        'score_source', 'source_reference_type', 'source_reference_id',
        'is_source_locked', 'source_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'score'           => 'float',
            'objective_score' => 'float',
            'theory_score'    => 'float',
            'entered_at'      => 'datetime',
            'is_source_locked'=> 'boolean',
            'source_synced_at'=> 'datetime',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        /*
         * A split examination can use raw Objective + Theory maxima that are
         * different from the examination's final result weight. Whenever both
         * portions are present, normalize their aggregate to the configured
         * assessment weight before persisting the score-sheet value.
         *
         * Example: Objective 42/50 + Theory 38/50 = 80/100. If Exam Weight is
         * 70, the persisted score is 56/70. Manual-only scores are untouched.
         */
        static::saving(function (Score $score): void {
            if ($score->objective_score === null || $score->theory_score === null || ! $score->assessment_type_id) {
                return;
            }

            $assessmentType = $score->relationLoaded('assessmentType')
                ? $score->assessmentType
                : AssessmentType::withoutTenantScope()->find($score->assessment_type_id);

            if (! $assessmentType || ! $assessmentType->isSplit()) {
                return;
            }

            $objectiveMax = (float) $assessmentType->objective_max;
            $theoryMax = (float) $assessmentType->theory_max;
            $rawMaximum = $objectiveMax + $theoryMax;
            $weight = (float) $assessmentType->weight_percentage;

            if ($rawMaximum <= 0 || $weight <= 0) {
                return;
            }

            $objective = max(0, min((float) $score->objective_score, $objectiveMax));
            $theory = max(0, min((float) $score->theory_score, $theoryMax));
            $weighted = (($objective + $theory) / $rawMaximum) * $weight;

            $score->score = round(max(0, min($weighted, $weight)), 2);
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function assessmentType(): BelongsTo { return $this->belongsTo(AssessmentType::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
    public function cbtExam(): BelongsTo { return $this->belongsTo(CbtExam::class); }
}
