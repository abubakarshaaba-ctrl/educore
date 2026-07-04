<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends BaseTenantModel
{
    protected $table = 'school_settings';

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'group',
    ];
}
