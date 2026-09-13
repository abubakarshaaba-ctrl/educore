<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicTopicBlock extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_required' => 'boolean',
            'is_approved' => 'boolean',
            'sequence' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(AcademicTopic::class, 'academic_topic_id');
    }
}
