<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class AssessmentType extends BaseTenantModel
{
    protected $table = 'assessment_types';

    protected $fillable = [
        'tenant_id',
        'term_id',
        'name',
        'weight_percentage',
        'objective_max',
        'theory_max',
        'is_exam',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'float',
            'objective_max'     => 'float',
            'theory_max'        => 'float',
            'is_exam'           => 'boolean',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Class levels this assessment type applies to.
     *
     * Backward compatibility: assessment types with no pivot rows are treated
     * as legacy/default term-wide assessment types. They are used only when a
     * class level has no explicitly scoped assessment types for that term.
     */
    public function classLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassLevel::class,
            'assessment_type_class_level',
            'assessment_type_id',
            'class_level_id'
        )->withTimestamps();
    }

    /**
     * Resolve the assessment configuration for one class level and term.
     * Explicit class-level configuration takes precedence over legacy/default
     * term-wide assessment types. This keeps existing schools working while
     * allowing different class categories to use different configurations.
     */
    public static function resolvedForClassLevel(int $termId, int $classLevelId): Collection
    {
        $scoped = static::query()
            ->where('term_id', $termId)
            ->whereHas('classLevels', fn ($q) => $q->where('class_levels.id', $classLevelId))
            ->orderBy('is_exam')
            ->orderBy('weight_percentage')
            ->orderBy('name')
            ->get();

        if ($scoped->isNotEmpty()) {
            return $scoped;
        }

        return static::query()
            ->where('term_id', $termId)
            ->whereDoesntHave('classLevels')
            ->orderBy('is_exam')
            ->orderBy('weight_percentage')
            ->orderBy('name')
            ->get();
    }

    public static function resolvedIdsForClassLevel(int $termId, int $classLevelId): array
    {
        return static::resolvedForClassLevel($termId, $classLevelId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Split-scored assessment types pull an objective score from a tagged
     * CBT exam (read-only) and combine it with a manually-entered theory
     * score. Plain assessment types take one manually-entered value.
     */
    public function isSplit(): bool
    {
        return $this->objective_max !== null && $this->theory_max !== null;
    }
}
