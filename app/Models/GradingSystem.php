<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingSystem extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'class_level_id',
        'grade_letter',
        'min_score',
        'max_score',
        'remark',
        'is_pass_grade',
        'grade_point',
    ];

    protected function casts(): array
    {
        return ['is_pass_grade' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    // Resolve grade letter from a raw score
    public static function resolve(int $classLevelId, float $score): ?self
    {
        return static::where('class_level_id', $classLevelId)
                     ->where('min_score', '<=', $score)
                     ->where('max_score', '>=', $score)
                     ->first();
    }
}
