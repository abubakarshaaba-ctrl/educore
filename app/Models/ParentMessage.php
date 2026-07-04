<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentMessage extends BaseTenantModel
{
    protected $table = 'parent_messages';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'from_user_id',
        'to_user_id',
        'subject',
        'body',
        'is_read',
        'read_at',
    ];
}
