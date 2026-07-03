<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends BaseTenantModel
{
    protected $table = 'announcements';

    protected $fillable = [
        'tenant_id',
        'title',
        'body',
        'audience',
        'priority',
        'publish_date',
        'expire_date',
        'is_published',
        'created_by',
    ];
}
