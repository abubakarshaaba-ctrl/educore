<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StudentSubjectSelection
 *
 * Records exactly which subjects each student is taking this session.
 * Compulsory subjects are auto-inserted when a student is assigned to a class.
 * Electives are selected per student here.
 */
class StudentSubjectSelection extends BaseTenantModel
{
    protected $table = 'student_subject_selections';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'class_level_id',
        'academic_track_id',
        'subject_id',
        'selection_type',   // compulsory | elective
        'session_id',
        'term_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function student(): BelongsTo    { return $this->belongsTo(Student::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function academicTrack(): BelongsTo { return $this->belongsTo(AcademicTrack::class); }
    public function subject(): BelongsTo   { return $this->belongsTo(Subject::class); }
    public function session(): BelongsTo   { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function term(): BelongsTo      { return $this->belongsTo(Term::class); }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)    { return $query->where('is_active', true); }
    public function scopeCompulsory($query){ return $query->where('selection_type','compulsory'); }
    public function scopeElective($query)  { return $query->where('selection_type','elective'); }
}
