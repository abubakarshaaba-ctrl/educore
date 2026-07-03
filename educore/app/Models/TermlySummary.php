<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermlySummary extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'class_arm_id', 'term_id', 'session_id',
        'total_score', 'final_average', 'position_in_class',
        'class_highest_avg',
        'class_lowest_avg', 'total_students_in_class',
        'subjects_offered', 'subjects_failed', 'subject_breakdown',
        'promotion_status', 'form_tutor_remark', 'principal_remark', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_breakdown' => 'array',
            'final_average'     => 'float',
            'computed_at'       => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }

    public function isPromoted(): bool { return $this->promotion_status === 'promoted'; }
    public function isRepeat(): bool   { return $this->promotion_status === 'repeat'; }
}
