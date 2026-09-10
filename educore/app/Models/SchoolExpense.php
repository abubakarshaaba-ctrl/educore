<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolExpense extends BaseTenantModel
{
    protected $table = 'school_expenses';

    protected $fillable = [
        'tenant_id',
        'session_id',
        'term_id',
        'title',
        'category',
        'amount',
        'expense_date',
        'payment_method',
        'reference',
        'description',
        'receipt_path',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'expense_date' => 'date',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
