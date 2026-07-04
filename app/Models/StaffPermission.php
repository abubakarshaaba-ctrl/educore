<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPermission extends Model
{
    protected $table = 'staff_permissions';

    protected $fillable = ['tenant_id','user_id','module','type','granted_by'];

    public function user()      { return $this->belongsTo(User::class, 'user_id'); }
    public function grantedBy() { return $this->belongsTo(User::class, 'granted_by'); }
}
