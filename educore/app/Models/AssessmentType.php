<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    protected static function booted(): void
    {
        static::addGlobalScope('score_entry_class_level', function (Builder $builder): void {
            if (! app()->bound('request')) {
                return;
            }

            $request = request();
            if (! $request->routeIs('scores.entry', 'scores.save')) {
                return;
            }

            $termId = (int) $request->input('term_id');
            $classArmId = (int) $request->input('class_arm_id');
            $tenantId = (int) (auth()->user()?->tenant_id ?? 0);
            if ($termId <= 0 || $classArmId <= 0 || $tenantId <= 0) {
                return;
            }

            $classLevelId = (int) DB::table('class_arms')
                ->where('tenant_id', $tenantId)
                ->where('id', $classArmId)
                ->value('class_level_id');

            if ($classLevelId <= 0) {
                return;
            }

            $hasExplicitConfiguration = DB::table('assessment_types')
                ->join('assessment_type_class_level', 'assessment_type_class_level.assessment_type_id', '=', 'assessment_types.id')
                ->where('assessment_types.tenant_id', $tenantId)
                ->where('assessment_types.term_id', $termId)
                ->where('assessment_type_class_level.class_level_id', $classLevelId)
                ->exists();

            $builder->where('assessment_types.term_id', $termId);

            if ($hasExplicitConfiguration) {
                $builder->whereHas('classLevels', fn ($q) => $q->where('class_levels.id', $classLevelId));
            } else {
                $builder->whereDoesntHave('classLevels');
            }
        });
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function classLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassLevel::class,
            'assessment_type_class_level',
            'assessment_type_id',
            'class_level_id'
        )->withTimestamps();
    }

    public static function resolvedForClassLevel(int $termId, int $classLevelId): Collection
    {
        $scoped = static::query()
            ->withoutGlobalScope('score_entry_class_level')
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
            ->withoutGlobalScope('score_entry_class_level')
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
     * Legacy objective/theory columns are retained for backwards-compatible
     * database reads only. Objective and theory are now scored entirely inside
     * the CBT module. Score Entry receives one final weighted aggregate from
     * CbtResultSyncService, so the score sheet must never expose split inputs.
     */
    public function isSplit(): bool
    {
        return false;
    }
}
