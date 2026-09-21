<?php

namespace App\Models;

class StaffAttendanceWorkingDay extends BaseTenantModel
{
    protected $table = 'staff_attendance_working_days';

    protected $fillable = [
        'tenant_id',
        'day_of_week',
        'is_working',
        'resumption_time',
        'closing_time',
        'grace_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_working' => 'boolean',
            'grace_minutes' => 'integer',
        ];
    }
}
