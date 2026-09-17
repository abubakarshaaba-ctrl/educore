<?php

namespace App\Models;

class StaffProfileSubmission extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','staff_id','name','email','phone','date_of_birth','gender','qualification','address',
        'employment_started_at','position_title','department_name','employment_type','functional_role',
        'grade_level','appointment_type','password_hash','status','reviewed_by','reviewed_at',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'employment_started_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
