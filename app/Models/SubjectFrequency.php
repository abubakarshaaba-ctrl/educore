<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectFrequency extends BaseTenantModel
{
    protected $table = 'subject_frequencies';

    protected $fillable = [
        'tenant_id', 'class_arm_id', 'subject_id', 'session_id', 'periods_per_week',
    ];

    protected function casts(): array
    {
        return ['periods_per_week' => 'integer'];
    }

    public function classArm(): BelongsTo  { return $this->belongsTo(ClassArm::class); }
    public function subject(): BelongsTo   { return $this->belongsTo(Subject::class); }
    public function session(): BelongsTo   { return $this->belongsTo(AcademicSession::class, 'session_id'); }
}
