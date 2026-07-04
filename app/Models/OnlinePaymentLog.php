<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlinePaymentLog extends BaseTenantModel
{
    protected $table = 'online_payment_logs';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'student_id',
        'gateway',
        'reference',
        'amount',
        'status',
        'gateway_response',
        'verified_at',
    ];

    protected $casts = ['gateway_response' => 'array'];
}
