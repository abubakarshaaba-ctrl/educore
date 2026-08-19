<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtSectionAttempt extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'cbt_student_session_id', 'cbt_exam_section_id', 'raw_score', 'maximum_score', 'status', 'scored_at'];
    protected function casts(): array { return ['raw_score' => 'float', 'maximum_score' => 'float', 'scored_at' => 'datetime']; }
    public function session(): BelongsTo { return $this->belongsTo(CbtStudentSession::class, 'cbt_student_session_id'); }
    public function section(): BelongsTo { return $this->belongsTo(CbtExamSection::class, 'cbt_exam_section_id'); }
}
