<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends BaseTenantModel
{
    protected $table = 'push_subscriptions';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'is_active',
    ];
}
