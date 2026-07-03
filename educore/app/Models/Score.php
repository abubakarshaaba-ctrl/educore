<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'student_id',
        'subject_id',
        'assessment_type_id',
        'term_id',
        'session_id',
        'entered_by',
        'score',
        'entered_at',
    ];

    protected function casts(): array
    {
        return [
            'score'      => 'float',
            'entered_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function assessmentType(): BelongsTo { return $this->belongsTo(AssessmentType::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
}
