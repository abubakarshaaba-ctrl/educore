<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumReportPublication extends BaseTenantModel
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'parallel_curriculum_class_id',
        'term_id',
        'status',
        'published_by',
        'published_at',
        'unpublished_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
