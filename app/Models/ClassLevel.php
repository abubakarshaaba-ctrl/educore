<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassLevel extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'section',        // junior | senior | general
        'order_index',
    ];

    // ── Existing relationships ─────────────────────────────────────────
    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function classArms(): HasMany  { return $this->hasMany(ClassArm::class); }
    public function gradingSystems(): HasMany { return $this->hasMany(GradingSystem::class); }
    public function promotionRule()       { return $this->hasOne(PromotionRule::class); }
    public function feeStructures(): HasMany { return $this->hasMany(FeeStructure::class); }

    // ── NEW: Curriculum relationships ──────────────────────────────────
    /**
     * Subject rules for this level (master curriculum definition).
     * Use subjectRulesForTrack($trackId) for filtered queries.
     */
    public function subjectRules(): HasMany
    {
        return $this->hasMany(ClassLevelSubject::class);
    }

    /**
     * All subjects assigned to this level through class_level_subjects.
     * Includes pivot fields: academic_track_id, subject_status, elective_group,
     * min_required, max_allowed, is_active.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_level_subjects')
                    ->withPivot([
                        'id',
                        'academic_track_id',
                        'subject_status',
                        'elective_group',
                        'min_required',
                        'max_allowed',
                        'is_active',
                    ])
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────────
    /**
     * Returns subject rules for a given track (or all if null).
     */
    public function subjectRulesForTrack(?int $trackId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->subjectRules()
            ->active()
            ->forTrack($trackId)
            ->offered()
            ->with('subject', 'academicTrack')
            ->get();
    }

    /**
     * Returns only compulsory subjects for a track.
     */
    public function compulsorySubjectsForTrack(?int $trackId): \Illuminate\Support\Collection
    {
        return $this->subjectRules()
            ->active()
            ->forTrack($trackId)
            ->compulsory()
            ->with('subject')
            ->get()
            ->pluck('subject');
    }

    public function isSenior(): bool
    {
        return strtolower($this->section ?? '') === 'senior';
    }

    // ── Scopes ─────────────────────────────────────────────────────────
    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
