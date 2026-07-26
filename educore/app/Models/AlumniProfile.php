<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniProfile extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'graduation_year', 'further_institution',
        'occupation', 'employer', 'contact_email', 'contact_phone', 'notes',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
