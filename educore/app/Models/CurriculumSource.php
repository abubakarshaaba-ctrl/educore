<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumSource extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_official' => 'boolean', 'is_active' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date', 'reviewed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function fragments(): HasMany { return $this->hasMany(CurriculumFragment::class); }

    public function scopeVisibleTo($query, ?int $tenantId)
    {
        return $query->where(fn ($scope) => $scope->whereNull('tenant_id')->orWhere('tenant_id', $tenantId));
    }
}
