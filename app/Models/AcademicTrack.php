<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AcademicTrack
 *
 * Represents an academic pathway: General (junior), Science, Humanities, Business (senior).
 * System-wide defaults have tenant_id = null. Schools may create custom tracks.
 */
class AcademicTrack extends BaseTenantModel
{
    protected $table = 'academic_tracks';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'section',   // junior | senior | general
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ── Disable global tenant scope for system defaults (tenant_id = null) ──
    protected static function booted(): void
    {
        // AcademicTrack doesn't use TenantScope because some rows are system-wide (tenant_id = null)
        // Queries must use ::forTenant() or ::systemAndTenant() explicitly
    }

    // ── Scopes ─────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, ?int $tenantId = null)
    {
        $tid = $tenantId ?? auth()->user()?->tenant_id;
        return $query->where(function ($q) use ($tid) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tid);
        })->orderBy('sort_order');
    }

    public function scopeSenior($query)
    {
        return $query->where('section', 'senior');
    }

    public function scopeJunior($query)
    {
        return $query->where('section', '!=', 'senior');
    }

    // ── Relationships ───────────────────────────────────────────────────
    public function classArms(): HasMany
    {
        return $this->hasMany(ClassArm::class);
    }

    public function subjectRules(): HasMany
    {
        return $this->hasMany(ClassLevelSubject::class);
    }

    public function studentSubjectSelections(): HasMany
    {
        return $this->hasMany(StudentSubjectSelection::class);
    }

    // ── Helpers ─────────────────────────────────────────────────────────
    public function isSenior(): bool  { return $this->section === 'senior'; }
    public function isJunior(): bool  { return in_array($this->section, ['general','junior']); }
    public function isPrimary(): bool { return $this->section === 'primary'; }
    public function isGeneral(): bool { return in_array($this->section, ['general','junior','primary']); }
}
