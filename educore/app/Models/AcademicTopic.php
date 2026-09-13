<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTopic extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'lesson_number' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CurriculumSource::class, 'curriculum_source_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(AcademicTopicBlock::class)->orderBy('sequence')->orderBy('id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
