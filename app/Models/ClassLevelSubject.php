<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassLevelSubject
 *
 * Master curriculum rule: defines whether a subject is compulsory, elective,
 * optional, or not_offered for a given ClassLevel + AcademicTrack combination.
 *
 * This is the single source of truth for what subjects are taught at each level.
 * ClassArmSubject is only for teacher allocation per class arm.
 */
class ClassLevelSubject extends BaseTenantModel
{
    protected $table = 'class_level_subjects';

    protected $fillable = [
        'tenant_id',
        'class_level_id',
        'academic_track_id',   // null = applies to all tracks (junior / general)
        'subject_id',
        'subject_status',      // compulsory | elective | optional | not_offered
        'elective_group',      // e.g. "Science Electives Group A"
        'min_required',
        'max_allowed',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'min_required' => 'integer',
            'max_allowed'  => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────
    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function academicTrack(): BelongsTo
    {
        return $this->belongsTo(AcademicTrack::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompulsory($query)
    {
        return $query->where('subject_status', 'compulsory');
    }

    public function scopeElective($query)
    {
        return $query->whereIn('subject_status', ['elective', 'optional']);
    }

    public function scopeOffered($query)
    {
        return $query->where('subject_status', '!=', 'not_offered');
    }

    public function scopeForTrack($query, ?int $trackId)
    {
        if ($trackId) {
            return $query->where(function ($q) use ($trackId) {
                $q->whereNull('academic_track_id')
                  ->orWhere('academic_track_id', $trackId);
            });
        }
        return $query->whereNull('academic_track_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    public function isCompulsory(): bool { return $this->subject_status === 'compulsory'; }
    public function isElective(): bool   { return in_array($this->subject_status, ['elective','optional']); }
    public function isOffered(): bool    { return $this->subject_status !== 'not_offered'; }

    public function statusBadgeColor(): string
    {
        return match ($this->subject_status) {
            'compulsory'  => '#059669',
            'elective'    => '#2563EB',
            'optional'    => '#D97706',
            'not_offered' => '#94A3B8',
            default       => '#94A3B8',
        };
    }
}
