<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtQuestionBank extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'subject_id', 'class_level_id', 'name', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function questions(): HasMany { return $this->hasMany(CbtQuestion::class, 'question_bank_id'); }
    public function exams(): HasMany { return $this->hasMany(CbtExam::class, 'question_bank_id'); }
}
