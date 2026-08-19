<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtQuestionScore extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'cbt_student_session_id', 'cbt_exam_section_id', 'cbt_question_id', 'score', 'maximum_score', 'scoring_method', 'status', 'scored_by', 'scored_at', 'feedback'];
    protected function casts(): array { return ['score' => 'float', 'maximum_score' => 'float', 'scored_at' => 'datetime']; }
    public function session(): BelongsTo { return $this->belongsTo(CbtStudentSession::class, 'cbt_student_session_id'); }
    public function section(): BelongsTo { return $this->belongsTo(CbtExamSection::class, 'cbt_exam_section_id'); }
    public function question(): BelongsTo { return $this->belongsTo(CbtQuestion::class, 'cbt_question_id'); }
    public function marker(): BelongsTo { return $this->belongsTo(User::class, 'scored_by'); }
}
