<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSupervision extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'exam_schedule_id', 'staff_id', 'role', 'notes',
    ];

    public function examSchedule(): BelongsTo { return $this->belongsTo(ExamSchedule::class); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_id'); }
}
