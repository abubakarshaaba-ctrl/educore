<?php

namespace App\Models;

use App\Models\BaseTenantModel;
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
