<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use App\Services\AssessmentTemplateService;
use App\Services\FinalClassGraduationService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'session_id',
        'name',
        'start_date',
        'end_date',
        'next_term_begins',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date'       => 'date',
            'end_date'         => 'date',
            'next_term_begins' => 'date',
            'is_current'       => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Term $term): void {
            // Keep the historical/global 30:70 fallback for schools or class
            // levels that have not adopted Assessment Templates yet.
            if (! $term->assessmentTypes()->whereDoesntHave('classLevels')->exists()) {
                $term->assessmentTypes()->createMany([
                    [
                        'tenant_id' => $term->tenant_id,
                        'name' => 'Continuous Assessment',
                        'weight_percentage' => 30,
                        'is_exam' => false,
                    ],
                    [
                        'tenant_id' => $term->tenant_id,
                        'name' => 'Examination',
                        'weight_percentage' => 70,
                        'is_exam' => true,
                    ],
                ]);
            }

            // Any class-level template assignments already made for this
            // academic session are inherited automatically by the new term.
            app(AssessmentTemplateService::class)->materializeAssignmentsForTerm($term);
        });

        static::updated(function (Term $term): void {
            if (!$term->wasChanged('is_current') || $term->is_current) {
                return;
            }

            app(FinalClassGraduationService::class)
                ->transitionForClosedTerm($term, auth()->user());
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function assessmentTypes(): HasMany
    {
        return $this->hasMany(AssessmentType::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function termSummaries(): HasMany
    {
        return $this->hasMany(TermlySummary::class);
    }

    public function termlySummaries(): HasMany
    {
        return $this->termSummaries();
    }

    public function termySummaries(): HasMany
    {
        return $this->termSummaries();
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
