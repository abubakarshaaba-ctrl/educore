<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonNoteRevision extends BaseTenantModel
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['content' => 'array', 'source_trace' => 'array', 'ai_generated' => 'boolean', 'teacher_edited' => 'boolean', 'approved_at' => 'datetime']; }
    public function lessonPlan(): BelongsTo { return $this->belongsTo(LessonPlan::class); }
}
