<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends BaseTenantModel
{
    use SoftDeletes;

    protected $table = 'admissions';

    protected $fillable = [
        'tenant_id',
        'application_number',
        'first_name',
        'last_name',
        'other_names',
        'date_of_birth',
        'gender',
        'religion',
        'nationality',
        'state_of_origin',
        'address',
        'applying_for_class_level_id',
        'previous_school',
        'previous_class',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'guardian_relationship',
        'guardian_occupation',
        'guardian_address',
        'status',
        'notes',
        'application_date',
        'reviewed_by',
        'decision_date',
        'enrolled_as_student_id',
        'payment_reference', 'payment_status',
        'interview_date', 'interview_notes',
        'offer_letter_sent', 'offer_sent_at',
    ];

    public function applyingForClassLevel()
    {
        return $this->belongsTo(\App\Models\ClassLevel::class, 'applying_for_class_level_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function enrolledStudent()
    {
        return $this->belongsTo(\App\Models\Student::class, 'enrolled_as_student_id');
    }
}
