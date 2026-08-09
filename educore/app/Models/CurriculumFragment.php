<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumFragment extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function source(): BelongsTo { return $this->belongsTo(CurriculumSource::class, 'curriculum_source_id'); }
}
