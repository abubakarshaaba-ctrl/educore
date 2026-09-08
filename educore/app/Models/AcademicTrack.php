<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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
        'section',   // primary | junior | senior | general
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // AcademicTrack intentionally does not use the normal tenant global scope
    // because tenant_id = null rows are platform defaults shared by all schools.
    // Every query that reads defaults must therefore use ::forTenant(), while
    // route-model binding below is deliberately stricter for mutation safety.
    protected static function booted(): void
    {
        static::deleting(function (AcademicTrack $track): void {
            $references = $track->classArms()->count()
                + $track->subjectRules()->count()
                + $track->studentSubjectSelections()->count();

            if ($references > 0) {
                throw ValidationException::withMessages([
                    'track' => 'This academic track is in use and cannot be deleted. Deactivate it instead.',
                ]);
            }
        });
    }

    /**
     * Route-bound tracks are mutation targets. A normal school user may bind
     * only a school-owned track; platform/system defaults and foreign-school
     * tracks cannot be addressed by changing the numeric route id.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $query = static::query()->where($field, $value);
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if (!$user->tenant_id) {
                return null;
            }
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->first();
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
