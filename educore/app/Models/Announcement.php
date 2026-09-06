<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'expire_date' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
