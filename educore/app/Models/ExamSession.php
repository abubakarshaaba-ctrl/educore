<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSession extends BaseTenantModel
{
    protected $table = 'exam_sessions';

    protected $fillable = ['tenant_id', 'exam_period_id', 'name', 'start_time', 'end_time', 'sort_order'];

    public function examPeriod(): BelongsTo { return $this->belongsTo(ExamPeriod::class); }
}
