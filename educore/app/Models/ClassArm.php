<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassArm extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'class_level_id',
        'academic_track_id',   // NEW: track for senior arms
        'form_tutor_id',
        'name',
    ];

    // ── Relationships ─────────────────────────────────────────────────
    public function tenant(): BelongsTo      { return $this->belongsTo(Tenant::class); }
    public function classLevel(): BelongsTo  { return $this->belongsTo(ClassLevel::class); }
    public function formTutor(): BelongsTo   { return $this->belongsTo(User::class, 'form_tutor_id'); }
    public function students(): HasMany      { return $this->hasMany(Student::class, 'current_class_arm_id'); }
    public function enrolments(): HasMany    { return $this->hasMany(StudentEnrollment::class); }
    public function enrollments(): HasMany   { return $this->enrolments(); }
    public function termlySummaries(): HasMany { return $this->hasMany(TermlySummary::class); }
    public function termlyummaries(): HasMany { return $this->termlySummaries(); }

    // NEW: Academic track (for senior class arms)
    public function academicTrack(): BelongsTo
    {
        return $this->belongsTo(AcademicTrack::class);
    }

    /**
     * Teacher–subject allocations for this class arm.
     * This is NOT the master subject list — it's only teacher assignment.
     * Source: class_arm_subjects table (repurposed to teacher allocation only).
     */
    public function teacherSubjectAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_arm_subjects')
                    ->withPivot(['id', 'teacher_id', 'session_id', 'term_id', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * @deprecated  Use teacherSubjectAssignments() or eligibleSubjects() instead.
     * Kept for backward compatibility — some old views reference ->subjects().
     */
    public function subjects(): BelongsToMany
    {
        return $this->teacherSubjectAssignments();
    }

    // ── Curriculum helpers ─────────────────────────────────────────────

    /**
     * Returns the academic track id for this arm.
     * Falls back to the General track slug if none assigned.
     */
    public function effectiveTrackId(): ?int
    {
        return $this->academic_track_id;
    }

    /**
     * All subject rules (ClassLevelSubject) applicable to this arm
     * based on class_level_id + academic_track_id.
     */
    public function eligibleSubjectRules(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->class_level_id) return collect();
        return $this->classLevel->subjectRulesForTrack($this->academic_track_id);
    }

    /**
     * Subjects actually offered in this arm (from ClassLevelSubject, not ClassArmSubject).
     * Returns a collection of Subject models with status pivot.
     */
    public function eligibleSubjects(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->class_level_id) return collect();

        $tid = $this->academic_track_id;

        return Subject::whereHas('classLevelRules', function ($q) use ($tid) {
            $q->where('class_level_id', $this->class_level_id)
              ->where('is_active', true)
              ->where('subject_status', '!=', 'not_offered')
              ->when($tid, fn($q2) =>
                  $q2->where(function ($q3) use ($tid) {
                      $q3->whereNull('academic_track_id')
                         ->orWhere('academic_track_id', $tid);
                  }),
                  fn($q2) => $q2->whereNull('academic_track_id')
              );
        })->orderBy('name')->get();
    }

    // ── Accessors ──────────────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return ($this->classLevel?->name ?? '') . ' ' . $this->name;
    }

    public function getTrackLabelAttribute(): string
    {
        return $this->academicTrack?->name ?? 'General';
    }
}
