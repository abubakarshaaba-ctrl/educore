<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'session_id', 'term_id', 'class_arm_id', 'subject_id',
        'title', 'exam_date', 'start_time', 'end_time', 'venue', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['exam_date' => 'date'];
    }

    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function supervisions(): HasMany { return $this->hasMany(ExamSupervision::class); }
}
