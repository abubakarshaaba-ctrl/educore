<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationQueue extends BaseTenantModel
{
    protected $table = 'notification_queue';

    protected $fillable = [
        'tenant_id',
        'channel',
        'recipient',
        'subject',
        'body',
        'gateway',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];
}
