<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamBodyRegistration extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'exam_body', 'exam_year', 'registration_number',
        'session_id', 'subjects', 'status', 'registered_by',
    ];

    protected function casts(): array
    {
        return ['subjects' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }
}
