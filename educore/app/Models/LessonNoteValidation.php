<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonNoteValidation extends BaseTenantModel
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['authority_alignment' => 'array', 'missing_plan_items' => 'array', 'missing_curriculum_items' => 'array', 'factual_concerns' => 'array', 'suggested_additions' => 'array']; }
    public function revision(): BelongsTo { return $this->belongsTo(LessonNoteRevision::class, 'lesson_note_revision_id'); }
}
