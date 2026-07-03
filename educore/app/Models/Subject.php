<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ── Existing relationships ─────────────────────────────────────────
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function scores(): HasMany   { return $this->hasMany(Score::class); }

    /**
     * @deprecated  Prefer classLevelRules() for curriculum queries.
     * Teacher assignments per class arm (class_arm_subjects).
     */
    public function classArms(): BelongsToMany
    {
        return $this->belongsToMany(ClassArm::class, 'class_arm_subjects')
                    ->withPivot(['id', 'teacher_id', 'session_id', 'term_id', 'is_active'])
                    ->withTimestamps();
    }

    // ── NEW: Curriculum relationships ──────────────────────────────────
    /**
     * All ClassLevelSubject rules that reference this subject.
     * Use to check whether/how this subject applies to a given level & track.
     */
    public function classLevelRules(): HasMany
    {
        return $this->hasMany(ClassLevelSubject::class);
    }

    /**
     * All class levels that include this subject (via class_level_subjects).
     */
    public function classLevels(): BelongsToMany
    {
        return $this->belongsToMany(ClassLevel::class, 'class_level_subjects')
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

    /**
     * Student elective selections for this subject.
     */
    public function studentSelections(): HasMany
    {
        return $this->hasMany(StudentSubjectSelection::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Subjects offered for a given class level + track (not_offered excluded).
     */
    public function scopeOfferedForLevelAndTrack($query, int $classLevelId, ?int $trackId)
    {
        return $query->whereHas('classLevelRules', function ($q) use ($classLevelId, $trackId) {
            $q->where('class_level_id', $classLevelId)
              ->where('is_active', true)
              ->where('subject_status', '!=', 'not_offered')
              ->when($trackId, fn($q2) =>
                  $q2->where(function ($q3) use ($trackId) {
                      $q3->whereNull('academic_track_id')
                         ->orWhere('academic_track_id', $trackId);
                  }),
                  fn($q2) => $q2->whereNull('academic_track_id')
              );
        });
    }
}
