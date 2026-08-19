<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtIntegrityEvent extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'cbt_exam_id', 'cbt_student_session_id', 'student_id', 'event_uuid', 'event_type', 'severity', 'ip_address', 'user_agent', 'metadata', 'occurred_at'];
    protected function casts(): array { return ['metadata' => 'array', 'occurred_at' => 'datetime']; }
    public function exam(): BelongsTo { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function session(): BelongsTo { return $this->belongsTo(CbtStudentSession::class, 'cbt_student_session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
