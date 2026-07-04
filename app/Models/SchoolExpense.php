<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
