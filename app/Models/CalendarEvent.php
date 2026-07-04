<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
