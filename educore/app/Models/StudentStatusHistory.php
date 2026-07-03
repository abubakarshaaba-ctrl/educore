<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'student_id',
        'old_status',
        'new_status',
        'effective_date',
        'reason',
        'destination_school',
        'transfer_certificate_number',
        'document_path',
        'changed_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
