<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentHealthRecord extends BaseTenantModel
{
    protected $table = 'student_health_records';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'blood_group',
        'genotype',
        'allergies',
        'chronic_conditions',
        'current_medications',
        'disability',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'doctor_name',
        'doctor_phone',
        'notes',
    ];
}
