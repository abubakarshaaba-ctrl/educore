<?php

namespace App\Models;

class CalendarEvent extends BaseTenantModel
{
    protected $table = 'calendar_events';

    protected $fillable = [
        'tenant_id',
        'session_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'color',
        'is_public',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_public' => 'boolean',
        ];
    }
}
