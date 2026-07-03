<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTransfer extends Model
{
    protected $table = 'student_transfers';

    protected $fillable = [
        'from_tenant_id',
        'to_tenant_id',
        'student_id',
        'student_name',
        'admission_number',
        'status',
        'reason',
        'requested_by',
        'approved_at',
    ];
}
