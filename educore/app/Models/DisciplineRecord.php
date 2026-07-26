<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineRecord extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'type', 'category', 'description', 'points',
        'occurred_at', 'suspension_start', 'suspension_end', 'action_taken',
        'status', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'date',
            'suspension_start' => 'date',
            'suspension_end' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
