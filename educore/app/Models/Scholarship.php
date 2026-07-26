<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scholarship extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'name', 'type', 'value', 'session_id', 'term_id',
        'reason', 'status', 'approved_by', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /** Amount this scholarship discounts off a given fee total. */
    public function discountFor(float $totalAmount): float
    {
        return match ($this->type) {
            'percentage'   => round($totalAmount * ((float) $this->value / 100), 2),
            'fixed_amount' => min((float) $this->value, $totalAmount),
            'full_waiver'  => $totalAmount,
            default        => 0,
        };
    }
}
