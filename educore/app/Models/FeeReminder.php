<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReminder extends BaseTenantModel
{
    protected $table = 'fee_reminders';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'invoice_id',
        'channel',
        'recipient',
        'message',
        'status',
        'sent_at',
    ];
}
