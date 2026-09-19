<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumStaffAttendanceRecord extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'user_id',
        'attendance_date',
        'status',
        'departure_status',
        'clock_in_time',
        'clock_out_time',
        'expected_resumption_time',
        'expected_closing_time',
        'grace_minutes',
        'clock_in_method',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'grace_minutes' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
