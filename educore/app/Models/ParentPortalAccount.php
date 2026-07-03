<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentPortalAccount extends BaseTenantModel
{
    protected $table = 'parent_portal_accounts';

    protected $fillable = [
        'tenant_id',
        'guardian_id',
        'email',
        'password',
        'is_active',
        'last_login',
    ];

    public function guardian() { return $this->belongsTo(\App\Models\Guardian::class, 'guardian_id'); }
}
