<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'hostel_id', 'room_id', 'session_id',
        'allocated_at', 'vacated_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'date',
            'vacated_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }
}
