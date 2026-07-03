<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryLoan extends BaseTenantModel
{
    protected $table = 'library_loans';

    protected $fillable = [
        'tenant_id',
        'book_id',
        'student_id',
        'staff_id',
        'issue_date',
        'due_date',
        'return_date',
        'status',
        'fine_amount',
        'fine_paid',
        'notes',
        'issued_by',
    ];

    public function book()    { return $this->belongsTo(LibraryBook::class, 'book_id'); }
    public function student() { return $this->belongsTo(\App\Models\Student::class); }
}
